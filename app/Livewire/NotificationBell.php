<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function markAllRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.notification-bell', [
            'notifications' => $user->notifications()->latest()->limit(20)->get(),
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }
}
