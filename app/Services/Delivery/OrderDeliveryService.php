<?php

namespace App\Services\Delivery;

use App\Models\DeliveryEvent;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\CustomerOrderNotifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * فلو الزبون (مثل SHEIN / Temu):
 * بانتظار التأكيد → قيد التجهيز → تم الشحن → خرج للتسليم → مستلم
 *
 * اللوجستي = مسار تفصيلي مرتّب مع ✓ يظهر للزبون أيضاً تحت مسار الحالة.
 */
class OrderDeliveryService
{
    /** حالات تظهر للأدمن في القائمة */
    public const STATUSES = [
        'pending' => 'بانتظار التأكيد',
        'confirmed' => 'قيد التجهيز', // legacy → نفس معنى التجهيز
        'preparing' => 'قيد التجهيز',
        'shipped' => 'تم الشحن',
        'out_for_delivery' => 'خرج للتسليم',
        'delivered' => 'مستلم',
        'failed_delivery' => 'تعذّر التسليم',
        'returned' => 'مرتجع',
        'cancelled' => 'ملغي',
        // legacy internal — تُعرض للزبون بمسمّى العميل لا باسم السائق
        'assigned' => 'قيد التجهيز',
        'picked_up' => 'تم الشحن',
    ];

    /** نص الزبون فقط (واجهة + واتساب + تتبع) */
    public const CUSTOMER_LABELS = [
        'pending' => 'بانتظار التأكيد',
        'confirmed' => 'قيد التجهيز',
        'preparing' => 'قيد التجهيز',
        'assigned' => 'قيد التجهيز',
        'picked_up' => 'تم الشحن',
        'shipped' => 'تم الشحن',
        'out_for_delivery' => 'خرج للتسليم',
        'delivered' => 'مستلم',
        'failed_delivery' => 'تعذّر التسليم',
        'returned' => 'مرتجع',
        'cancelled' => 'ملغي',
    ];

    /**
     * مراحل لوجستية مرتّبة — تظهر للزبون كشيكليست.
     * (إسناد المندوب داخلي ولا يدخل هالقائمة)
     */
    public const LOGISTICS = [
        'local_warehouse' => 'مستودع محلي',
        'packing' => 'استلام تغليف',
        'hub' => 'وصل للسنتر',
        'international' => 'الشحن بالخارج',
        'qadmous_syria' => 'الشحن قدموس داخل سوريا',
        'out_with_driver' => 'خرج مع المندوب',
    ];

    /** مفتاح داخلي فقط (مش بالشيكليست للزبون) */
    public const LOGISTICS_INTERNAL = [
        'driver_assigned' => 'إسناد مندوب',
    ];

    public const FAILURE_REASONS = [
        'customer_absent' => 'الزبون غير موجود',
        'customer_refused' => 'الزبون رفض الاستلام',
        'wrong_address' => 'عنوان خاطئ',
        'cash_issue' => 'مشكلة تحصيل كاش',
        'damaged' => 'الطرد تالف',
        'other' => 'سبب آخر',
    ];

    /** @var array<string, list<string>> */
    private array $transitions = [
        'pending' => ['preparing', 'cancelled'],
        'confirmed' => ['preparing', 'shipped', 'cancelled'], // legacy
        'preparing' => ['shipped', 'cancelled'],
        'assigned' => ['shipped', 'out_for_delivery', 'cancelled'], // legacy
        'picked_up' => ['shipped', 'out_for_delivery', 'cancelled'], // legacy
        'shipped' => ['out_for_delivery', 'delivered', 'failed_delivery', 'cancelled'],
        'out_for_delivery' => ['delivered', 'failed_delivery', 'cancelled'],
        'failed_delivery' => ['out_for_delivery', 'returned', 'cancelled'],
        'returned' => ['cancelled'],
        'delivered' => [],
        'cancelled' => [],
    ];

    /** حالات يبلّغ فيها الزبون واتساب (مو كل حدث لوجستي) */
    private array $customerNotifyStatuses = [
        'pending',
        'preparing',
        'confirmed',
        'shipped',
        'out_for_delivery',
        'delivered',
        'failed_delivery',
        'cancelled',
        'returned',
    ];

    public function statusLabel(string $status): string
    {
        return self::STATUSES[$status] ?? $status;
    }

    public function customerStatusLabel(string $status): string
    {
        return self::CUSTOMER_LABELS[$status] ?? $this->statusLabel($status);
    }

    public function customerStatusKey(string $status): string
    {
        return match ($status) {
            'confirmed', 'assigned' => 'preparing',
            'picked_up' => 'shipped',
            default => $status,
        };
    }

    public function allowedNext(string $status): array
    {
        return $this->transitions[$status] ?? [];
    }

    /** الحالات التي يختارها الأدمن يدوياً (بدون legacy) */
    public function adminSelectableStatuses(): array
    {
        return [
            'pending' => 'بانتظار التأكيد',
            'preparing' => 'قيد التجهيز',
            'shipped' => 'تم الشحن',
            'out_for_delivery' => 'خرج للتسليم',
            'delivered' => 'مستلم',
            'failed_delivery' => 'تعذّر التسليم',
            'returned' => 'مرتجع',
            'cancelled' => 'ملغي',
        ];
    }

    public function generateTrackingNumber(Order $order): string
    {
        return 'WAS'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT).Str::upper(Str::random(4));
    }

    public function generateOtp(): string
    {
        return (string) random_int(100000, 999999);
    }

    /**
     * إسناد سائق = لوجستي داخلي. ما يغيّر حالة الزبون إلى «تم إسناد سائق».
     */
    public function assignDriver(Order $order, User $driver, ?User $actor = null): Order
    {
        if (in_array($order->status, ['delivered', 'cancelled', 'returned'], true)) {
            throw ValidationException::withMessages([
                'driver_id' => 'لا يمكن إسناد سائق لطلب منتهٍ.',
            ]);
        }

        return DB::transaction(function () use ($order, $driver, $actor) {
            $otp = $order->delivery_otp ?: $this->generateOtp();
            $tracking = $order->tracking_number ?: $this->generateTrackingNumber($order);

            $order->update([
                'driver_id' => $driver->id,
                'delivery_otp' => $otp,
                'delivery_otp_verified_at' => null,
                'delivery_otp_attempts' => 0,
                'tracking_number' => $tracking,
                'assigned_at' => now(),
            ]);

            $this->record($order, $order->status, 'لوجستي: إسناد مندوب', $actor, [
                'logistics' => 'driver_assigned',
                'driver_id' => $driver->id,
                'driver_name' => $driver->name,
                'customer_visible' => false,
            ], (self::LOGISTICS_INTERNAL['driver_assigned'] ?? 'إسناد مندوب').' — '.$driver->name.' · تتبع: '.$tracking);

            // لا واتساب «تم إسناد سائق» — الزبون يبقى على حالته الحالية
            return $order->fresh(['driver', 'deliveryEvents']);
        });
    }

    public function addLogisticsNote(Order $order, string $logisticsKey, ?User $actor = null, ?string $note = null): Order
    {
        $allLabels = self::LOGISTICS + self::LOGISTICS_INTERNAL;
        if (! isset($allLabels[$logisticsKey])) {
            throw ValidationException::withMessages([
                'logistics' => 'مرحلة لوجستية غير معروفة.',
            ]);
        }

        // لا تكرار لنفس المرحلة
        $already = $order->deliveryEvents()
            ->get()
            ->contains(fn (DeliveryEvent $e) => ($e->meta['logistics'] ?? null) === $logisticsKey);

        if ($already) {
            return $order->fresh(['deliveryEvents']);
        }

        $visible = isset(self::LOGISTICS[$logisticsKey]);
        $label = $allLabels[$logisticsKey];
        $title = $visible ? $label : ('لوجستي: '.$label);

        $this->record($order, $order->status, $title, $actor, [
            'logistics' => $logisticsKey,
            'customer_visible' => $visible,
        ], $note ?: $label);

        return $order->fresh(['deliveryEvents']);
    }

    /** شيكليست لوجستي مرتّبة (للأدمن + الزبون) */
    public function logisticsChecklist(Order $order): array
    {
        $order->loadMissing('deliveryEvents');
        $doneMap = [];

        foreach ($order->deliveryEvents as $event) {
            $key = $event->meta['logistics'] ?? null;
            if ($key && isset(self::LOGISTICS[$key]) && ! isset($doneMap[$key])) {
                $doneMap[$key] = [
                    'at' => optional($event->created_at)->toIso8601String(),
                    'note' => $event->note,
                ];
            }
        }

        $steps = [];
        $foundCurrent = false;
        foreach (self::LOGISTICS as $key => $label) {
            $done = isset($doneMap[$key]);
            $current = ! $done && ! $foundCurrent;
            if ($current) {
                $foundCurrent = true;
            }
            $steps[] = [
                'key' => $key,
                'label' => $label,
                'done' => $done,
                'current' => $current,
                'at' => $doneMap[$key]['at'] ?? null,
                'note' => $doneMap[$key]['note'] ?? null,
            ];
        }

        // إذا كل شيء منجز — ما في current
        if (! $foundCurrent) {
            foreach ($steps as &$step) {
                $step['current'] = false;
            }
            unset($step);
        }

        return $steps;
    }

    public function customerStatusSteps(Order $order): array
    {
        $order->loadMissing('deliveryEvents');

        $orderKeys = ['placed', 'confirmed', 'preparing', 'packed', 'shipped', 'out_for_delivery', 'delivered'];
        $labels = [
            'placed' => 'تم استلام الطلب',
            'confirmed' => 'تم التأكيد',
            'preparing' => 'قيد التجهيز',
            'packed' => 'تم التغليف',
            'shipped' => 'تم الشحن',
            'out_for_delivery' => 'خرج للتسليم',
            'delivered' => 'تم التسليم',
        ];

        $key = $this->customerStatusKey($order->status);
        if (in_array($key, ['cancelled', 'returned', 'failed_delivery'], true)) {
            return collect($orderKeys)->map(fn ($stepKey) => [
                'key' => $stepKey,
                'label' => $labels[$stepKey],
                'done' => false,
                'current' => false,
            ])->all();
        }

        $packingDone = $order->deliveryEvents
            ->contains(fn (DeliveryEvent $e) => ($e->meta['logistics'] ?? null) === 'packing');

        // Map live status → furthest completed milestone index
        $idx = match ($key) {
            'pending' => 0,
            'preparing' => $packingDone ? 3 : 2,
            'shipped' => 4,
            'out_for_delivery' => 5,
            'delivered' => 6,
            default => 0,
        };

        // preparing implies confirmed; shipped implies packed
        if ($key === 'preparing' && $idx < 2) {
            $idx = 2;
        }
        if (in_array($key, ['shipped', 'out_for_delivery', 'delivered'], true)) {
            $idx = max($idx, 4);
        }

        return collect($orderKeys)->map(function ($stepKey, $i) use ($idx, $key, $labels) {
            $done = $key === 'delivered' ? true : $i < $idx;
            $current = $key === 'delivered' ? $stepKey === 'delivered' : $i === $idx;

            return [
                'key' => $stepKey,
                'label' => $labels[$stepKey],
                'done' => $done,
                'current' => $current,
            ];
        })->all();
    }

    /** هل يقدر الزبون يلغي الطلب؟ pending دائماً، preparing اختياري، بعد الشحن لا */
    public function canCustomerCancel(Order $order): bool
    {
        $key = $this->customerStatusKey($order->status);

        return in_array($key, ['pending', 'preparing'], true);
    }

    /** always | maybe | never */
    public function customerCancelPolicy(Order $order): string
    {
        $key = $this->customerStatusKey($order->status);

        return match ($key) {
            'pending' => 'always',
            'preparing' => 'maybe',
            default => 'never',
        };
    }

    public function estimatedDelivery(Order $order): array
    {
        $method = $order->shipping_method ?: 'standard';
        $cfg = config('shipping.methods.'.$method, config('shipping.methods.standard', []));
        $placed = $order->placed_at ?? $order->created_at;
        $etaLabel = $cfg['eta'] ?? null;

        return [
            'shipping_method' => $method,
            'shipping_method_label' => $cfg['label'] ?? $method,
            'eta_label' => $etaLabel,
            'placed_at' => optional($placed)->toIso8601String(),
            'live_location' => null, // لاحقاً
        ];
    }

    public function transition(Order $order, string $to, ?User $actor = null, ?string $note = null, array $meta = []): Order
    {
        $from = $order->status;
        // Normalize legacy confirmed → treat like preparing for forward moves
        if ($to === 'confirmed') {
            $to = 'preparing';
        }

        $allowed = $this->allowedNext($from);

        if (! in_array($to, $allowed, true) && $from !== $to) {
            throw ValidationException::withMessages([
                'status' => "لا يمكن الانتقال من «{$this->statusLabel($from)}» إلى «{$this->statusLabel($to)}».",
            ]);
        }

        return DB::transaction(function () use ($order, $to, $actor, $note, $meta, $from) {
            $data = ['status' => $to];

            if (in_array($to, ['preparing', 'shipped'], true) && ! $order->tracking_number) {
                $data['tracking_number'] = $this->generateTrackingNumber($order);
            }
            if ($to === 'shipped' && ! $order->delivery_otp) {
                $data['delivery_otp'] = $this->generateOtp();
            }
            if ($to === 'out_for_delivery') {
                $data['out_for_delivery_at'] = now();
                if (! $order->delivery_otp) {
                    $data['delivery_otp'] = $this->generateOtp();
                }
            }
            if ($to === 'delivered') {
                $data['delivered_at'] = now();
                $data['failure_reason'] = null;
            }
            if ($to === 'failed_delivery') {
                $data['failed_at'] = now();
                if (! empty($meta['failure_reason'])) {
                    $data['failure_reason'] = $meta['failure_reason'];
                }
            }
            if ($to === 'cancelled') {
                $data['canceled_at'] = now();
            }

            $order->update($data);

            // كاش عند الاستلام: اعتبار الدفع مكتمل عند التسليم
            if ($to === 'delivered') {
                $payment = $order->payment;
                if ($payment && $payment->payment_method === 'cash_on_delivery' && $payment->status !== 'paid') {
                    $payment->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'collected_on_delivery' => true,
                        ]),
                    ]);
                }
            }

            $this->record(
                $order,
                $to,
                $this->customerStatusLabel($to),
                $actor,
                array_merge($meta, ['from' => $from, 'customer_visible' => true]),
                $note
            );

            if ($to === 'out_for_delivery') {
                $this->addLogisticsNote($order->fresh(), 'out_with_driver', $actor, 'خرج مع المندوب للتوصيل');
            }

            $fresh = $order->fresh(['driver', 'deliveryEvents', 'user', 'shippingAddress']);

            if (in_array($to, $this->customerNotifyStatuses, true)) {
                app(CustomerOrderNotifier::class)->notifyLocalStatus($fresh, $to, [
                    'otp' => $fresh->getAttributes()['delivery_otp'] ?? null,
                    'failure_label' => $meta['failure_label'] ?? null,
                ]);
                app(\App\Services\Orders\OrderAlertService::class)->notifyCustomerInApp($fresh, $to);
                app(\App\Services\Orders\OrderAlertService::class)->alertAdminsStatus($fresh, $to);
            }

            return $fresh;
        });
    }

    public function verifyOtp(Order $order, string $otp, ?User $actor = null): Order
    {
        if (! in_array($order->status, ['out_for_delivery', 'shipped', 'assigned', 'picked_up'], true)) {
            throw ValidationException::withMessages([
                'otp' => 'الطلب ليس في مرحلة تسمح بالتحقق من رمز الاستلام.',
            ]);
        }

        if ($order->delivery_otp_verified_at) {
            return $order;
        }

        if ((string) $order->delivery_otp !== trim($otp)) {
            $order->increment('delivery_otp_attempts');
            throw ValidationException::withMessages([
                'otp' => 'رمز الاستلام غير صحيح.',
            ]);
        }

        $order->update([
            'delivery_otp_verified_at' => now(),
            'delivery_otp_attempts' => 0,
        ]);

        $this->record($order, $order->status, 'تم التحقق من رمز الاستلام (OTP)', $actor, [
            'otp_verified' => true,
            'customer_visible' => true,
        ]);

        return $order->fresh();
    }

    public function storeProof(Order $order, ?UploadedFile $signature = null, ?UploadedFile $photo = null, ?User $actor = null): Order
    {
        $data = [];

        if ($signature) {
            $data['signature_path'] = $signature->store('delivery/signatures', 'public');
        }
        if ($photo) {
            $data['proof_photo_path'] = $photo->store('delivery/proofs', 'public');
        }

        if ($data) {
            $order->update($data);
            $this->record($order, $order->status, 'توقيع / إثبات الاستلام', $actor, array_merge($data, [
                'customer_visible' => true,
            ]));
        }

        return $order->fresh();
    }

    public function markDelivered(Order $order, ?string $otp = null, ?UploadedFile $signature = null, ?UploadedFile $photo = null, ?User $actor = null, bool $requireOtp = true): Order
    {
        return DB::transaction(function () use ($order, $otp, $signature, $photo, $actor, $requireOtp) {
            if ($requireOtp) {
                if (! $order->delivery_otp_verified_at) {
                    if ($otp === null || $otp === '') {
                        throw ValidationException::withMessages([
                            'otp' => 'يلزم إدخال رمز الاستلام قبل التسليم.',
                        ]);
                    }
                    $this->verifyOtp($order, $otp, $actor);
                    $order->refresh();
                }
            }

            $this->storeProof($order, $signature, $photo, $actor);
            $order->refresh();

            return $this->transition($order, 'delivered', $actor, 'مستلم — تم التوقيع/الإثبات');
        });
    }

    public function markFailed(Order $order, string $reason, ?string $note = null, ?User $actor = null): Order
    {
        if (! isset(self::FAILURE_REASONS[$reason])) {
            throw ValidationException::withMessages([
                'failure_reason' => 'سبب الفشل غير صالح.',
            ]);
        }

        $label = self::FAILURE_REASONS[$reason];

        return $this->transition($order, 'failed_delivery', $actor, $note ?: $label, [
            'failure_reason' => $reason,
            'failure_label' => $label,
        ]);
    }

    public function record(Order $order, string $status, string $title, ?User $actor = null, array $meta = [], ?string $note = null): DeliveryEvent
    {
        return DeliveryEvent::create([
            'order_id' => $order->id,
            'actor_id' => $actor?->id,
            'status' => $status,
            'title' => $title,
            'note' => $note,
            'meta' => $meta ?: null,
        ]);
    }

    public function ensureInitialEvent(Order $order): void
    {
        if ($order->deliveryEvents()->exists()) {
            return;
        }

        $this->record($order, $order->status ?: 'pending', $this->customerStatusLabel($order->status ?: 'pending'), $order->user, [
            'placed_at' => optional($order->placed_at)->toIso8601String(),
            'customer_visible' => true,
        ]);
    }

    /** خط زمني للزبون: حالات الطلب + مراحل اللوجستي الظاهرة */
    public function customerTimeline(Order $order)
    {
        $order->loadMissing('deliveryEvents');

        return $order->deliveryEvents
            ->filter(function (DeliveryEvent $e) {
                $meta = $e->meta ?? [];

                return ($meta['customer_visible'] ?? true) !== false;
            })
            ->map(function (DeliveryEvent $e) {
                $meta = $e->meta ?? [];
                $logisticsKey = $meta['logistics'] ?? null;
                $isLogistics = $logisticsKey && isset(self::LOGISTICS[$logisticsKey]);

                return [
                    'status' => $isLogistics ? $logisticsKey : $this->customerStatusKey($e->status),
                    'kind' => $isLogistics ? 'logistics' : 'status',
                    'title' => $isLogistics
                        ? (self::LOGISTICS[$logisticsKey] ?? $e->title)
                        : ($e->title ?: $this->customerStatusLabel($e->status)),
                    'note' => $e->note,
                    'at' => optional($e->created_at)->toIso8601String(),
                ];
            })
            ->values();
    }
}
