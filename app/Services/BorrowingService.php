<?php

namespace App\Services;

use App\Exceptions\BorrowingStateException;
use App\Models\Equipment;
use App\Models\Extension;
use App\Models\Request as LoanRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BorrowingService
{
    private const ACTIVE_REQUEST_STATUSES = ['Pending', 'Approved', 'Checked Out'];

    private const RETURN_CONDITIONS = ['Excellent', 'Good', 'Fair', 'Poor', 'Damaged'];

    public function createRequest(User $user, int $equipmentId, array $attributes): LoanRequest
    {
        return DB::transaction(function () use ($user, $equipmentId, $attributes) {
            $equipment = Equipment::query()->lockForUpdate()->findOrFail($equipmentId);

            if ($equipment->status !== 'Available') {
                throw new BorrowingStateException('This equipment is no longer available.');
            }

            $hasActiveRequest = LoanRequest::query()
                ->where('equipment_id', $equipment->id)
                ->where('user_id', $user->id)
                ->whereIn('status', self::ACTIVE_REQUEST_STATUSES)
                ->lockForUpdate()
                ->exists();

            if ($hasActiveRequest) {
                throw new BorrowingStateException('You already have an active request for this equipment.');
            }

            $endDate = Carbon::parse($attributes['end_date']);
            if ($endDate->isWeekend()) {
                $endDate->next(Carbon::MONDAY);
            }

            return LoanRequest::create([
                'equipment_id' => $equipment->id,
                'user_id' => $user->id,
                'purpose' => $attributes['purpose'],
                'start_date' => $attributes['start_date'],
                'end_date' => $endDate->toDateString(),
                'status' => 'Pending',
            ])->load('equipment');
        });
    }

    public function approve(int $requestId): LoanRequest
    {
        return DB::transaction(function () use ($requestId) {
            $request = LoanRequest::query()->lockForUpdate()->findOrFail($requestId);
            $equipment = Equipment::query()->lockForUpdate()->findOrFail($request->equipment_id);

            if ($request->status !== 'Pending') {
                throw new BorrowingStateException('This request has already been processed.');
            }

            if ($equipment->status !== 'Available') {
                throw new BorrowingStateException("{$equipment->name} is no longer available for reservation.");
            }

            $request->update(['status' => 'Approved', 'decided_at' => now()]);
            $equipment->update(['status' => 'Reserved']);

            return $request->fresh(['equipment', 'user']);
        });
    }

    public function reject(int $requestId, ?string $reason = null): LoanRequest
    {
        return DB::transaction(function () use ($requestId, $reason) {
            $request = LoanRequest::query()->lockForUpdate()->findOrFail($requestId);

            if ($request->status !== 'Pending') {
                throw new BorrowingStateException('This request has already been processed.');
            }

            $request->update([
                'status' => 'Rejected',
                'decided_at' => now(),
                'reject_reason' => $reason,
            ]);

            return $request->fresh(['equipment', 'user']);
        });
    }

    public function checkOut(int $requestId): LoanRequest
    {
        return DB::transaction(function () use ($requestId) {
            $request = LoanRequest::query()->lockForUpdate()->findOrFail($requestId);
            $equipment = Equipment::query()->lockForUpdate()->findOrFail($request->equipment_id);

            if ($request->status !== 'Approved' || $equipment->status !== 'Reserved') {
                throw new BorrowingStateException('This equipment is not ready to be checked out.');
            }

            $request->update(['status' => 'Checked Out', 'checked_out_at' => now()]);
            $equipment->update(['status' => 'Checked Out']);

            return $request->fresh(['equipment', 'user']);
        });
    }

    public function checkIn(int $requestId, string $condition, ?string $notes = null): LoanRequest
    {
        if (! in_array($condition, self::RETURN_CONDITIONS, true)) {
            throw new BorrowingStateException('The selected return condition is invalid.');
        }

        return DB::transaction(function () use ($requestId, $condition, $notes) {
            $request = LoanRequest::query()->lockForUpdate()->findOrFail($requestId);
            $equipment = Equipment::query()->lockForUpdate()->findOrFail($request->equipment_id);

            if ($request->status !== 'Checked Out' || $equipment->status !== 'Checked Out') {
                throw new BorrowingStateException('This equipment is not currently checked out.');
            }

            $damaged = $condition === 'Damaged';
            $normalizedCondition = $damaged ? 'Poor' : $condition;

            $request->update([
                'status' => 'Returned',
                'returned_at' => now(),
                'return_condition' => $normalizedCondition,
                'return_notes' => $notes,
            ]);
            $equipment->update([
                'condition' => $normalizedCondition,
                'status' => $damaged ? 'Maintenance' : 'Available',
            ]);

            return $request->fresh(['equipment', 'user']);
        });
    }

    public function resolveExtension(int $extensionId, bool $approve): Extension
    {
        return DB::transaction(function () use ($extensionId, $approve) {
            $extension = Extension::query()->lockForUpdate()->findOrFail($extensionId);
            $request = LoanRequest::query()->lockForUpdate()->findOrFail($extension->request_id);

            if ($extension->status !== 'Pending' || $request->status !== 'Checked Out') {
                throw new BorrowingStateException('This extension request can no longer be resolved.');
            }

            if ($approve) {
                if (! $extension->new_end->gt($request->end_date)) {
                    throw new BorrowingStateException('An extension must move the due date later.');
                }

                $request->update(['end_date' => $extension->new_end->toDateString()]);
            }

            $extension->update([
                'status' => $approve ? 'Approved' : 'Declined',
                'decided_at' => now(),
            ]);

            return $extension->fresh(['request.equipment', 'request.user']);
        });
    }
}
