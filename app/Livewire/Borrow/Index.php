<?php

namespace App\Livewire\Borrow;

use App\Exceptions\BorrowingStateException;
use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\User;
use App\Notifications\SystemAlert;
use App\Services\BorrowingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Borrow Equipment', 'subtitle' => 'Submit a borrowing request'])]
class Index extends Component
{
    public string $equipmentId = '';

    public string $startDate = '';

    public string $endDate = '';

    /** @var array<string, string>|null */
    public ?array $submittedRequest = null;

    public function mount(): void
    {
        $this->startDate = now()->toDateString();
        $this->endDate = $this->defaultEndDate($this->startDate);
    }

    public function updatedStartDate(): void
    {
        if ($this->startDate !== '') {
            $this->endDate = $this->defaultEndDate($this->startDate);
        }
    }

    public function submit(): void
    {
        $this->validate([
            'equipmentId' => 'required|integer|exists:equipment,id',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        $user = Auth::user();

        try {
            $request = app(BorrowingService::class)->createRequest($user, (int) $this->equipmentId, [
                'purpose' => 'QR borrowing entry request.',
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
            ]);
        } catch (BorrowingStateException $e) {
            session()->flash('toast', [$e->getMessage(), 'err']);

            return;
        }

        $equipment = $request->equipment;
        AuditLog::record('Request submitted', "{$user->name} requested {$equipment->name} ({$equipment->asset_tag}) via borrowing entry.", $user);

        foreach (User::where('role', 'admin')->get() as $admin) {
            $admin->notify(new SystemAlert("New borrowing request from {$user->name} for {$equipment->name}.", 'info'));
        }

        $this->submittedRequest = [
            'equipment' => "{$equipment->name} — {$equipment->asset_tag}",
            'start_date' => $request->start_date->format('M j, Y'),
            'end_date' => $request->end_date->format('M j, Y'),
            'status' => $request->status,
        ];
        $this->equipmentId = '';
    }

    public function render()
    {
        return view('livewire.borrow.index', [
            'equipment' => Equipment::query()
                ->available()
                ->orderBy('name')
                ->orderBy('asset_tag')
                ->get(),
        ]);
    }

    private function defaultEndDate(string $startDate): string
    {
        $date = Carbon::parse($startDate)->addDays(2);

        if ($date->isWeekend()) {
            $date->next(Carbon::MONDAY);
        }

        return $date->toDateString();
    }
}
