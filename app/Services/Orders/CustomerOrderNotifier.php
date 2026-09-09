<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\Delivery\OrderDeliveryService;
use App\Services\Whatsapp\WhatsappGatewayClient;
use Illuminate\Support\Facades\Log;

class CustomerOrderNotifier
{
    public function __construct(private WhatsappGatewayClient $whatsapp)
    {
    }

    public function notifyLocalOrderCreated(Order $order): void
    {
        $track = url('/orders/'.$order->id.'/track');
        $label = app(OrderDeliveryService::class)->customerStatusLabel($order->status);

        if ($order->status === 'preparing') {
            $this->send(
                $this->phoneFromOrder($order),
                "وصلة ✓\nتم تأكيد طلبك #{$order->id}.\nالحالة: {$label}\nبدأنا التحضير.\n{$track}"
            );

            return;
        }

        $this->send(
            $this->phoneFromOrder($order),
            "وصلة ✓\nاستلمنا طلبك #{$order->id}.\nالحالة: {$label}\nمنؤكّد الدفع ونبلّش التجهيز.\n{$track}"
        );
    }

    public function notifyLocalStatus(Order $order, string $status, array $extra = []): void
    {
        $delivery = app(OrderDeliveryService::class);
        $label = $delivery->customerStatusLabel($status);
        $track = url('/orders/'.$order->id.'/track');
        $otp = $extra['otp'] ?? $order->getAttributes()['delivery_otp'] ?? null;

        // لا نرسل واتساب عن إسناد سائق / لوجستي داخلي
        if (in_array($status, ['assigned', 'picked_up'], true)) {
            return;
        }

        $msg = match ($status) {
            'confirmed', 'preparing' => "وصلة ✓\nطلبك #{$order->id}: *{$label}*\nعم نجهّز طلبك.\n{$track}",
            'shipped' => "وصلة\nطلبك #{$order->id}: *تم الشحن*\n"
                .($otp ? "رمز الاستلام عند التوصيل: *{$otp}*\n" : '')
                ."{$track}",
            'out_for_delivery' => "وصلة 🚗\nطلبك #{$order->id}: *خرج للتسليم*\n"
                .($otp ? "رمز الاستلام: *{$otp}*\n" : '')
                ."جهّزي التوقيع عند الاستلام.\n{$track}",
            'delivered' => "وصلة ✓\nطلبك #{$order->id}: *مستلم*\nشكراً لتسوقك معنا!",
            'failed_delivery' => "وصلة\nطلبك #{$order->id}: تعذّر التسليم"
                .(isset($extra['failure_label']) ? " ({$extra['failure_label']})" : '')
                ."\nمنعيد المحاولة.\n{$track}",
            'cancelled' => "وصلة\nتم إلغاء طلبك #{$order->id}.",
            'returned' => "وصلة\nطلبك #{$order->id} مرتجع.",
            'pending' => "وصلة\nطلبك #{$order->id}: بانتظار التأكيد.\n{$track}",
            default => "وصلة\nتحديث طلب #{$order->id}: {$label}\n{$track}",
        };

        $this->send($this->phoneFromOrder($order), $msg);
    }

    public function notifyExternalCreated(PurchaseRequest $pr): void
    {
        // إذا مربوط بطلبية موحّدة — الإشعار من الطلبية يكفي
        if ($pr->order_id) {
            return;
        }

        $source = $pr->platform?->name ?: 'خارجي';
        $this->send(
            $this->phoneFromUser($pr->customer),
            "وصلة ✓\nاستلمنا طلبك من {$source} #{$pr->id}."
        );
    }

    public function notifyExternalStatus(PurchaseRequest $pr): void
    {
        if ($pr->order_id) {
            // تحديثات الشراء الخارجي تظهر ضمن الطلبية؛ إشعار مختصر اختياري عند وصول المستودع
            if ($pr->status === PurchaseRequest::STATUS_RECEIVED) {
                $source = $pr->platform?->name ?: 'خارجي';
                $this->send(
                    $this->phoneFromUser($pr->customer),
                    "وصلة\nصنف {$source} ضمن طلبك #{$pr->order_id} وصل المستودع — رح يتكمّل التجهيز/الشحن."
                );
            }

            return;
        }

        $source = $pr->platform?->name ?: 'خارجي';
        $label = PurchaseRequest::STATUS_LABELS[$pr->status] ?? $pr->status;
        $link = url('/my-requests');

        $msg = match ($pr->status) {
            PurchaseRequest::STATUS_QUOTED => "وصلة\nعرض سعر لطلب {$source} #{$pr->id}\n{$link}",
            PurchaseRequest::STATUS_PAID => "وصلة ✓\nتم دفع طلب {$source} #{$pr->id}",
            PurchaseRequest::STATUS_RECEIVED => "وصلة ✓\nطلب {$source} #{$pr->id} وصل المستودع",
            default => "وصلة\nتحديث {$source} #{$pr->id}: {$label}",
        };

        $this->send($this->phoneFromUser($pr->customer), $msg);
    }

    private function phoneFromOrder(Order $order): ?string
    {
        $order->loadMissing(['user', 'shippingAddress']);

        return $this->normalizePhone(
            $order->shippingAddress?->phone
            ?: $order->user?->phone
        );
    }

    private function phoneFromUser(?User $user): ?string
    {
        return $this->normalizePhone($user?->phone);
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $phone);

        return $digits !== '' ? $digits : null;
    }

    private function send(?string $phone, string $message): void
    {
        if (! $phone) {
            Log::info('WhatsApp skipped: no customer phone', ['preview' => mb_substr($message, 0, 80)]);

            return;
        }

        if (! $this->whatsapp->isEnabled()) {
            Log::info('WhatsApp skipped: gateway disabled', ['phone' => $phone]);

            return;
        }

        try {
            $this->whatsapp->sendToPhone($phone, $message);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp send failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
