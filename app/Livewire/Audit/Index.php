<?php

namespace App\Livewire\Audit;

use App\Models\AuditLog;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Audit Log'])]
class Index extends Component
{
    public function render()
    {
        return view('livewire.audit.index', [
            'logs' => AuditLog::latest()->limit(200)->get(),
        ]);
    }
}
