<?php

namespace App\Livewire\Loans;

use App\Livewire\Concerns\ManagesRequests;
use App\Models\AuditLog;
use App\Models\Request as LoanRequest;
use App\Notifications\SystemAlert;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Active Loans'])]
class Index extends Component
{
    use ManagesRequests;

    public ?int $checkingInId = null;

    public string $checkInCondition = 'Good';

    public string $checkInNotes = '';

    public function openCheckIn(int $id): void
    {
        $this->checkingInId = $id;
        $this->checkInCondition = 'Good';
        $this->checkInNotes = '';
    }

    public function confirmCheckIn(): void
    {
        $this->checkIn($this->checkingInId, $this->checkInCondition, $this->checkInNotes);
        $this->checkingInId = null;
    }

    public function remind(int $id): void
    {
        $request = LoanRequest::with('equipment', 'user')->findOrFail($id);
        Gate::authorize('remind', $request);

        AuditLog::record('Reminder sent', "Overdue reminder sent to {$request->user->name} for {$request->equipment->name}.", Auth::user());
        $request->user->notify(new SystemAlert("Reminder: {$request->equipment->name} is overdue. Please return it as soon as possible.", 'warn'));
        session()->flash('toast', ["Reminder sent to {$request->user->name}.", 'info']);
    }

    public function render()
    {
        $loans = LoanRequest::with('equipment', 'user')
            ->whereIn('status', ['Checked Out', 'Approved'])
            ->orderBy('end_date')
            ->get();

        $overdueCount = $loans->filter(fn ($r) => $r->isOverdue())->count();

        return view('livewire.loans.index', [
            'loans' => $loans,
            'overdueCount' => $overdueCount,
        ]);
    }
}
