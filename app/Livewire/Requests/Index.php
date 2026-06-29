<?php

namespace App\Livewire\Requests;

use App\Livewire\Concerns\ManagesRequests;
use App\Models\Request as LoanRequest;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Requests'])]
class Index extends Component
{
    use ManagesRequests;

    public string $filter = 'All';

    public ?int $rejectingId = null;

    public string $rejectReason = '';

    public ?int $checkingInId = null;

    public string $checkInCondition = 'Good';

    public string $checkInNotes = '';

    public function openReject(int $id): void
    {
        $this->rejectingId = $id;
        $this->rejectReason = '';
    }

    public function confirmReject(): void
    {
        $this->reject($this->rejectingId, $this->rejectReason);
        $this->rejectingId = null;
    }

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

    public function render()
    {
        $counts = LoanRequest::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $list = LoanRequest::with(['equipment', 'user', 'extension', 'damageReport'])
            ->when($this->filter !== 'All', fn ($q) => $q->where('status', $this->filter))
            ->latest('created_at')
            ->get();

        return view('livewire.requests.index', [
            'list' => $list,
            'pendingCount' => $counts->get('Pending', 0),
            'segments' => ['All', 'Pending', 'Approved', 'Checked Out', 'Returned', 'Rejected'],
        ]);
    }
}
