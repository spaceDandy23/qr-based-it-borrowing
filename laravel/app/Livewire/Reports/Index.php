<?php

namespace App\Livewire\Reports;

use App\Models\Equipment;
use App\Models\Request as LoanRequest;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Reports'])]
class Index extends Component
{
    public function render()
    {
        $totalEquipment = Equipment::count();
        $inUse = Equipment::whereIn('status', ['Checked Out', 'Reserved'])->count();
        $utilization = $totalEquipment ? round($inUse / $totalEquipment * 100) : 0;

        $totalRequests = LoanRequest::count();
        $returned = LoanRequest::where('status', 'Returned')->get();
        $onTime = $returned->filter(fn ($r) => $r->returned_at && $r->returned_at->lte($r->end_date->endOfDay()))->count();
        $onTimeRate = $returned->count() ? round($onTime / $returned->count() * 100) : 100;

        $topBorrowed = LoanRequest::query()
            ->join('equipment', 'equipment.id', '=', 'requests.equipment_id')
            ->selectRaw('equipment.name as name, count(*) as total')
            ->groupBy('equipment.name')
            ->orderByDesc('total')
            ->limit(6)
            ->pluck('total', 'name');

        $outcomes = collect(['Pending', 'Approved', 'Checked Out', 'Returned', 'Rejected'])
            ->mapWithKeys(fn ($s) => [$s => LoanRequest::where('status', $s)->count()]);

        return view('livewire.reports.index', [
            'utilization' => $utilization,
            'totalRequests' => $totalRequests,
            'completedLoans' => $returned->count(),
            'onTimeRate' => $onTimeRate,
            'topBorrowed' => $topBorrowed,
            'outcomes' => $outcomes,
        ]);
    }
}
