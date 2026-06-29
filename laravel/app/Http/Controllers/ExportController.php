<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\Request as LoanRequest;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function equipment(): StreamedResponse
    {
        $rows = Equipment::orderByDesc('id')->get();

        AuditLog::record('Export', 'inventory exported to CSV.', Auth::user());

        return $this->csv("babadook-inventory-".now()->toDateString().'.csv', function ($out) use ($rows) {
            fputcsv($out, ['Asset Tag', 'Name', 'Category', 'Serial', 'Condition', 'Status', 'Location', 'Purchase Date']);
            foreach ($rows as $e) {
                fputcsv($out, [$e->asset_tag, $e->name, $e->category, $e->serial, $e->condition, $e->status, $e->location, optional($e->purchase_date)->toDateString()]);
            }
        });
    }

    public function requests(): StreamedResponse
    {
        $rows = LoanRequest::with('equipment', 'user')->orderByDesc('id')->get();

        AuditLog::record('Export', 'borrowing-history exported to CSV.', Auth::user());

        return $this->csv('babadook-borrowing-history-'.now()->toDateString().'.csv', function ($out) use ($rows) {
            fputcsv($out, ['Asset Tag', 'Equipment', 'Borrower', 'Email', 'Purpose', 'Start', 'End', 'Status', 'Returned Condition']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->equipment->asset_tag ?? '',
                    $r->equipment->name ?? '',
                    $r->user->name,
                    $r->user->email,
                    $r->purpose,
                    $r->start_date->toDateString(),
                    $r->end_date->toDateString(),
                    $r->status,
                    $r->return_condition ?? '',
                ]);
            }
        });
    }

    private function csv(string $filename, callable $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($writer) {
            $out = fopen('php://output', 'w');
            $writer($out);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
