<?php

namespace App\Livewire\History;

use App\Models\Request as LoanRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Borrowing History'])]
class Index extends Component
{
    public function render()
    {
        return view('livewire.history.index', [
            'mine' => LoanRequest::with('equipment')->where('user_id', Auth::id())->latest('created_at')->get(),
        ]);
    }
}
