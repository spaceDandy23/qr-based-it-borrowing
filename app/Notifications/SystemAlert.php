<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class SystemAlert extends Notification
{
    public function __construct(
        private readonly string $text,
        private readonly string $type = 'info',
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return ['text' => $this->text, 'type' => $this->type];
    }
}
