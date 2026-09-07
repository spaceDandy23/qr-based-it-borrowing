<?php

namespace App\Livewire\Concerns;

use App\Exceptions\BorrowingStateException;
use App\Models\AuditLog;
use App\Models\Extension;
use App\Models\Request as LoanRequest;
use App\Notifications\SystemAlert;
use App\Services\BorrowingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

trait ManagesRequests
{
    public function approve(int $requestId): void
    {
        $request = LoanRequest::findOrFail($requestId);
        Gate::authorize('decide', $request);

        try {
            $request = app(BorrowingService::class)->approve($requestId);
        } catch (BorrowingStateException $e) {
            $this->flashBorrowingError($e);

            return;
        }

        $equipment = $request->equipment;

        AuditLog::record('Request approved', "{$equipment->name} ({$equipment->asset_tag}) reserved for {$request->user->name}.", Auth::user());
        $request->user->notify(new SystemAlert("Your request for {$equipment->name} was approved and reserved.", 'ok'));
        session()->flash('toast', ['Request approved — equipment reserved.', 'ok']);
    }

    public function reject(int $requestId, string $reason = ''): void
    {
        $request = LoanRequest::findOrFail($requestId);
        Gate::authorize('decide', $request);

        try {
            $request = app(BorrowingService::class)->reject($requestId, $reason);
        } catch (BorrowingStateException $e) {
            $this->flashBorrowingError($e);

            return;
        }

        AuditLog::record('Request rejected', "{$request->equipment->name} request from {$request->user->name} declined.", Auth::user());
        $request->user->notify(new SystemAlert("Your request for {$request->equipment->name} was rejected.", 'bad'));
        session()->flash('toast', ['Request rejected.', 'info']);
    }

    public function checkOut(int $requestId): void
    {
        $request = LoanRequest::findOrFail($requestId);
        Gate::authorize('checkOut', $request);

        try {
            $request = app(BorrowingService::class)->checkOut($requestId);
        } catch (BorrowingStateException $e) {
            $this->flashBorrowingError($e);

            return;
        }

        AuditLog::record('Checked out', "{$request->equipment->name} ({$request->equipment->asset_tag}) checked out to {$request->user->name}.", Auth::user());
        $request->user->notify(new SystemAlert("{$request->equipment->name} is checked out to you. Due {$request->end_date->format('M j, Y')}.", 'ok'));
        session()->flash('toast', ["{$request->equipment->name} checked out to {$request->user->name}.", 'ok']);
    }

    public function checkIn(int $requestId, string $condition, ?string $notes = null): void
    {
        $request = LoanRequest::findOrFail($requestId);
        Gate::authorize('checkIn', $request);

        try {
            $request = app(BorrowingService::class)->checkIn($requestId, $condition, $notes);
        } catch (BorrowingStateException $e) {
            $this->flashBorrowingError($e);

            return;
        }

        $equipment = $request->equipment;

        AuditLog::record('Checked in', "{$equipment->name} returned by {$request->user->name} in {$condition} condition.", Auth::user());
        $request->user->notify(new SystemAlert("Return confirmed for {$equipment->name}. Thank you.", 'ok'));
        session()->flash('toast', ["{$equipment->name} checked in ({$condition}).", 'ok']);
    }

    public function resolveExtension(int $extensionId, bool $approve): void
    {
        $extension = Extension::with('request')->findOrFail($extensionId);
        $request = $extension->request;
        Gate::authorize('resolveExtension', $request);

        try {
            $extension = app(BorrowingService::class)->resolveExtension($extensionId, $approve);
        } catch (BorrowingStateException $e) {
            $this->flashBorrowingError($e);

            return;
        }

        $request = $extension->request;
        if ($approve) {
            AuditLog::record('Extension approved', "{$request->equipment->name} due date moved to {$extension->new_end->format('M j, Y')}.", Auth::user());
            $request->user->notify(new SystemAlert("Extension approved — {$request->equipment->name} now due {$extension->new_end->format('M j, Y')}.", 'ok'));
            session()->flash('toast', ['Extension approved.', 'ok']);
        }

    }

    private function flashBorrowingError(BorrowingStateException $e): void
    {
        session()->flash('toast', [$e->getMessage(), 'err']);
    }
}
