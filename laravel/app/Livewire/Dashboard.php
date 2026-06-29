<?php

namespace App\Livewire;

use App\Livewire\Concerns\ManagesRequests;
use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\Request as LoanRequest;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Dashboard'])]
class Dashboard extends Component
{
    use ManagesRequests;

    public function render()
    {
        $statusCounts = Equipment::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $categoryCounts = Equipment::query()
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        return view('livewire.dashboard', [
            'total' => Equipment::count(),
            'available' => Equipment::where('status', 'Available')->count(),
            'borrowed' => Equipment::where('status', 'Checked Out')->count(),
            'overdue' => LoanRequest::where('status', 'Checked Out')->whereDate('end_date', '<', now())->count(),
            'pending' => LoanRequest::where('status', 'Pending')->count(),
            'pendingList' => LoanRequest::with('equipment', 'user')->where('status', 'Pending')->latest()->limit(5)->get(),
            'recentLogs' => AuditLog::latest()->limit(7)->get(),
            'statusCounts' => $statusCounts,
            'categoryCounts' => $categoryCounts,
        ]);
    }
}
