<?php

namespace App\Livewire\Browse;

use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\Request as LoanRequest;
use App\Notifications\SystemAlert;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Browse Equipment'])]
class Index extends Component
{
    public string $q = '';

    public string $category = 'All';

    public string $availability = 'All';

    public ?int $borrowingId = null;

    public string $purpose = '';

    public string $startDate = '';

    public string $endDate = '';

    public function mount()
    {
        $this->q = request()->query('q', '');
        $this->startDate = now()->toDateString();
        $this->endDate = now()->addDays(7)->toDateString();
    }

    public function openBorrow(int $id): void
    {
        $this->borrowingId = $id;
        $this->purpose = '';
        $this->startDate = now()->toDateString();
        $this->endDate = now()->addDays(7)->toDateString();
    }

    public function submitBorrow(): void
    {
        $this->validate([
            'purpose' => 'required|string|max:500',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        $equipment = Equipment::findOrFail($this->borrowingId);
        $user = Auth::user();

        LoanRequest::create([
            'equipment_id' => $equipment->id,
            'user_id' => $user->id,
            'purpose' => $this->purpose,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'status' => 'Pending',
        ]);

        AuditLog::record('Request submitted', "{$user->name} requested {$equipment->name} ({$equipment->asset_tag}).", $user);

        foreach (\App\Models\User::where('role', 'admin')->get() as $admin) {
            $admin->notify(new SystemAlert("New borrowing request from {$user->name} for {$equipment->name}.", 'info'));
        }

        $this->borrowingId = null;
        session()->flash('toast', ['Request submitted for review.', 'ok']);

        $this->redirect(route('my-requests'));
    }

    public function render()
    {
        $categories = ['All', ...Equipment::query()->distinct()->orderBy('category')->pluck('category')];

        $equipment = Equipment::query()
            ->search($this->q)
            ->when($this->category !== 'All', fn ($q) => $q->where('category', $this->category))
            ->when($this->availability === 'Available', fn ($q) => $q->where('status', 'Available'))
            ->orderByDesc('id')
            ->get();

        return view('livewire.browse.index', [
            'equipment' => $equipment,
            'categories' => $categories,
        ]);
    }
}
