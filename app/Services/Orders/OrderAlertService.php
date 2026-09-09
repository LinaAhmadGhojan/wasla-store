<?php

namespace App\Services\Orders;

use App\Models\AdminAlert;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusCustomerNotification;
use App\Services\Delivery\OrderDeliveryService;
use App\Services\Push\FcmPushService;
use Illuminate\Support\Facades\Log;

/**
 * تنبيهات الداشبورد + إشعار داخل التطبيق (DB) + Push FCM للموبايل.
 * واتساب يبقى عبر CustomerOrderNotifier.
 */
class OrderAlertService
{
    public function __construct(
        private OrderDeliveryService $delivery,
        private FcmPushService $fcm,
    ) {
    }

    public function alertAdminsNewOrder(Order $order): AdminAlert
    {
        $order->loadMissing(['user', 'items.platform', 'payment']);
        $sources = method_exists($order, 'sourcesLabel') ? $order->sourcesLabel() : 'وصلة';
        $customer = $order->user?->name ?: 'زبون';
        $total = number_format((float) $order->total, 2).' د.إ';
        $status = $this->delivery->customerStatusLabel($order->status);

        $title = "طلب جديد #{$order->id}";
        $body = "{$customer} · {$sources} · {$total} · {$status}";
        $speak = "وصلك طلب جديد رقم {$order->id} من {$customer}";

        return $this->pushAdmin('order_new', $title, $body, $speak, route('admin.orders.show', $order), [
            'order_id' => $order->id,
            'status' => $order->status,
            'sources' => $sources,
        ]);
    }

    public function alertAdminsStatus(Order $order, string $status): ?AdminAlert
    {
        // لا نملأ الداشبورد بكل لوجستي — فقط محطات مهمة
        if (! in_array($status, ['preparing', 'shipped', 'out_for_delivery', 'delivered', 'failed_delivery', 'cancelled'], true)) {
            return null;
        }

        $label = $this->delivery->customerStatusLabel($status);
        $title = "تحديث طلب #{$order->id}";
        $body = "الحالة صارت: {$label}";
        $speak = null; // TTS فقط للطلبات الجديدة عادةً

        return $this->pushAdmin('order_status', $title, $body, $speak, route('admin.orders.show', $order), [
            'order_id' => $order->id,
            'status' => $status,
        ]);
    }

    public function notifyCustomerInApp(Order $order, string $status, ?string $extra = null): void
    {
        $order->loadMissing('user');
        $user = $order->user;
        if (! $user) {
            return;
        }

        $statusLabel = $this->delivery->customerStatusLabel($status);
        $trackUrl = url('/orders/'.$order->id.'/track');
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
        $title = $titles[$status] ?? ('تحديث طلب #'.$order->id);
        $body = $extra ?: ("الحالة: {$statusLabel}");

        try {
            $user->notify(new OrderStatusCustomerNotification(
                orderId: $order->id,
                status: $status,
                statusLabel: $statusLabel,
                trackUrl: $trackUrl,
                extra: $extra
            ));
        } catch (\Throwable $e) {
            Log::warning('Customer in-app notification failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $this->fcm->sendToUser($user, $title, $body, [
                'type' => 'order_status',
                'order_id' => (string) $order->id,
                'status' => $status,
                'status_label' => $statusLabel,
                'action_url' => $trackUrl,
            ], 'customer');
        } catch (\Throwable $e) {
            Log::warning('Customer FCM failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function pushAdmin(
        string $type,
        string $title,
        ?string $body = null,
        ?string $speakText = null,
        ?string $actionUrl = null,
        array $meta = []
    ): AdminAlert {
        return AdminAlert::create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'speak_text' => $speakText,
            'action_url' => $actionUrl,
            'meta' => $meta ?: null,
        ]);
    }

    /** أدمن المستخدمين (إن وُجدوا) — اختياري لاحقاً مع auth */
    public function adminUsers()
    {
        return User::query()
            ->whereHas('role', fn ($q) => $q->whereIn('name', ['admin', 'seller']))
            ->get();
    }
}
