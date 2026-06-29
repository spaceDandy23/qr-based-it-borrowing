<?php

namespace App\Livewire\Concerns;

use App\Models\AuditLog;
use App\Models\Request as LoanRequest;
use App\Notifications\SystemAlert;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

trait ManagesRequests
{
    public function approve(int $requestId): void
    {
        $request = LoanRequest::with('equipment', 'user')->findOrFail($requestId);
        Gate::authorize('decide', $request);

        $equipment = $request->equipment;

        if (! in_array($equipment->status, ['Available', 'Reserved'])) {
            session()->flash('toast', ["{$equipment->name} is {$equipment->status} and can't be reserved.", 'err']);

            return;
        }

        $request->update(['status' => 'Approved', 'decided_at' => now()]);
        $equipment->update(['status' => 'Reserved']);

        AuditLog::record('Request approved', "{$equipment->name} ({$equipment->asset_tag}) reserved for {$request->user->name}.", Auth::user());
        $request->user->notify(new SystemAlert("Your request for {$equipment->name} was approved and reserved.", 'ok'));
        session()->flash('toast', ['Request approved — equipment reserved.', 'ok']);
    }

    public function reject(int $requestId, string $reason = ''): void
    {
        $request = LoanRequest::with('equipment', 'user')->findOrFail($requestId);
        Gate::authorize('decide', $request);

        $request->update(['status' => 'Rejected', 'decided_at' => now(), 'reject_reason' => $reason]);

        AuditLog::record('Request rejected', "{$request->equipment->name} request from {$request->user->name} declined.", Auth::user());
        $request->user->notify(new SystemAlert("Your request for {$request->equipment->name} was rejected.", 'bad'));
        session()->flash('toast', ['Request rejected.', 'info']);
    }

    public function checkOut(int $requestId): void
    {
        $request = LoanRequest::with('equipment', 'user')->findOrFail($requestId);
        Gate::authorize('checkOut', $request);

        $request->update(['status' => 'Checked Out', 'checked_out_at' => now()]);
        $request->equipment->update(['status' => 'Checked Out']);

        AuditLog::record('Checked out', "{$request->equipment->name} ({$request->equipment->asset_tag}) checked out to {$request->user->name}.", Auth::user());
        $request->user->notify(new SystemAlert("{$request->equipment->name} is checked out to you. Due {$request->end_date->format('M j, Y')}.", 'ok'));
        session()->flash('toast', ["{$request->equipment->name} checked out to {$request->user->name}.", 'ok']);
    }

    public function checkIn(int $requestId, string $condition, ?string $notes = null): void
    {
        $request = LoanRequest::with('equipment', 'user')->findOrFail($requestId);
        Gate::authorize('checkIn', $request);

        $equipment = $request->equipment;
        $damaged = $condition === 'Damaged';

        $request->update([
            'status' => 'Returned',
            'returned_at' => now(),
            'return_condition' => $damaged ? 'Poor' : $condition,
        ]);

        $equipment->update([
            'condition' => $damaged ? 'Poor' : $condition,
            'status' => $damaged ? 'Maintenance' : 'Available',
        ]);

        AuditLog::record('Checked in', "{$equipment->name} returned by {$request->user->name} in {$condition} condition.", Auth::user());
        $request->user->notify(new SystemAlert("Return confirmed for {$equipment->name}. Thank you.", 'ok'));
        session()->flash('toast', ["{$equipment->name} checked in ({$condition}).", 'ok']);
    }

    public function resolveExtension(int $extensionId, bool $approve): void
    {
        $extension = \App\Models\Extension::with('request.equipment', 'request.user')->findOrFail($extensionId);
        $request = $extension->request;
        Gate::authorize('resolveExtension', $request);

        if ($approve) {
            $request->update(['end_date' => $extension->new_end]);
            AuditLog::record('Extension approved', "{$request->equipment->name} due date moved to {$extension->new_end->format('M j, Y')}.", Auth::user());
            $request->user->notify(new SystemAlert("Extension approved — {$request->equipment->name} now due {$extension->new_end->format('M j, Y')}.", 'ok'));
            session()->flash('toast', ['Extension approved.', 'ok']);
        }

        $extension->update(['status' => $approve ? 'Approved' : 'Declined', 'decided_at' => now()]);
    }
}
