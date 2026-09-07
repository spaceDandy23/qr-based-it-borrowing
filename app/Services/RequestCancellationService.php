<?php

namespace App\Services;

use App\Exceptions\BorrowingStateException;
use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\Request as LoanRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RequestCancellationService
{
    public function cancel(int $requestId, User $actor): LoanRequest
    {
        return DB::transaction(function () use ($requestId, $actor) {
            $request = LoanRequest::query()->lockForUpdate()->with('equipment')->findOrFail($requestId);

            if (! $actor->isAdmin() && $request->user_id !== $actor->id) {
                throw new BorrowingStateException('You can only cancel your own requests.');
            }

            if (! in_array($request->status, ['Pending', 'Approved'], true)) {
                throw new BorrowingStateException('This request can no longer be cancelled.');
            }

            $releasedReservation = false;
            if ($request->status === 'Approved') {
                $equipment = Equipment::query()->lockForUpdate()->findOrFail($request->equipment_id);

                if ($equipment->status !== 'Reserved') {
                    throw new BorrowingStateException('This reservation can no longer be cancelled safely.');
                }

                $equipment->update(['status' => 'Available']);
                $releasedReservation = true;
            }

            $request->update(['status' => 'Cancelled']);

            $cancelledBy = $actor->isAdmin() && $request->user_id !== $actor->id
                ? "by admin {$actor->name}"
                : 'by borrower';
            $detail = "{$request->equipment->name} ({$request->equipment->asset_tag}) request cancelled {$cancelledBy}.";
            if ($releasedReservation) {
                $detail .= ' Reservation released.';
            }

            AuditLog::record('Request cancelled', $detail, $actor);

            return $request->fresh(['equipment', 'user']);
        });
    }
}
