<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStatusCustomerNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $status,
        public string $statusLabel,
        public string $trackUrl,
        public ?string $extra = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $titles = [
            'pending' => 'استلمنا طلبك',
            'preparing' => 'طلبك قيد التجهيز',
            'confirmed' => 'طلبك قيد التجهيز',
            'shipped' => 'تم شحن طلبك',
            'out_for_delivery' => 'طلبك خرج للتسليم',
            'delivered' => 'تم استلام طلبك',
            'failed_delivery' => 'تعذّر تسليم طلبك',
            'cancelled' => 'تم إلغاء طلبك',
        ];

        return [
            'type' => 'order_status',
            'order_id' => $this->orderId,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'title' => $titles[$this->status] ?? ('تحديث طلب #'.$this->orderId),
            'body' => $this->extra ?: ("الحالة: {$this->statusLabel}"),
            'action_url' => $this->trackUrl,
        ];
    }
}
