<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderPlacedAdminNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $title,
        public string $body,
        public ?string $actionUrl = null,
        public ?string $speakText = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_new',
            'order_id' => $this->orderId,
            'title' => $this->title,
            'body' => $this->body,
            'action_url' => $this->actionUrl,
            'speak_text' => $this->speakText,
        ];
    }
}
