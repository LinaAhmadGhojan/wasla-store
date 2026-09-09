<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PurchaseRequest;
use App\Models\Vendor;
use App\Services\Delivery\OrderDeliveryService;
use App\Services\Orders\CustomerOrderNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function __construct(private OrderDeliveryService $delivery)
    {
    }

    public function index(Request $request)
    {
        $orders = $request->user()->orders()
            ->with(['items.product', 'items.platform', 'items.variant', 'shippingAddress', 'payment', 'driver'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Order $order) => $this->serializeOrder($order, includeOtp: false));

        return response()->json($orders);
    }

    public function show(Request $request, Order $order)
    {
        $this->authorize('view', $order);
        $order->load(['items.product', 'items.platform', 'items.variant', 'payment', 'shippingAddress', 'driver', 'deliveryEvents', 'purchaseRequests.platform']);
        $this->delivery->ensureInitialEvent($order);

        return response()->json($this->serializeOrder(
            $order->fresh(['items.product', 'items.platform', 'deliveryEvents', 'driver', 'payment', 'shippingAddress', 'purchaseRequests.platform']),
            includeOtp: true
        ));
    }

    public function tracking(Request $request, Order $order)
    {
        $this->authorize('view', $order);
        $order->load(['driver', 'deliveryEvents', 'shippingAddress', 'payment', 'items.platform']);
        $this->delivery->ensureInitialEvent($order);
        $order = $order->fresh(['driver', 'deliveryEvents', 'shippingAddress', 'payment', 'items.platform']);

        return response()->json([
            'order_id' => $order->id,
            'status' => $order->status,
            'status_label' => $this->delivery->customerStatusLabel($order->status),
            'status_key' => $this->delivery->customerStatusKey($order->status),
            'sources' => $order->sourcesLabel(),
            'tracking_number' => $order->tracking_number,
            'cancellation_reason' => $order->cancellation_reason,
            'can_cancel' => $this->delivery->canCustomerCancel($order),
            'cancel_policy' => $this->delivery->customerCancelPolicy($order),
            'can_reorder' => $order->items->contains(fn ($i) => (bool) $i->product_id),
            'invoice_url' => url('/orders/'.$order->id.'/invoice'),
            'estimated_delivery' => $this->delivery->estimatedDelivery($order),
            'live_location' => null,
            'delivery_otp' => in_array($order->status, ['shipped', 'out_for_delivery', 'assigned', 'picked_up'], true)
                ? ($order->getAttributes()['delivery_otp'] ?? null)
                : null,
            'otp_verified' => (bool) $order->delivery_otp_verified_at,
            'driver' => ($order->driver && in_array($this->delivery->customerStatusKey($order->status), ['shipped', 'out_for_delivery', 'delivered'], true))
                ? [
                    'id' => $order->driver->id,
                    'name' => $order->driver->name,
                    'phone' => $order->driver->phone,
                ]
                : null,
            'failure_reason' => $order->failure_reason,
            'failure_label' => $order->failure_reason
                ? (OrderDeliveryService::FAILURE_REASONS[$order->failure_reason] ?? $order->failure_reason)
                : null,
            'signature_url' => $order->signature_path ? Storage::disk('public')->url($order->signature_path) : null,
            'proof_photo_url' => $order->proof_photo_path ? Storage::disk('public')->url($order->proof_photo_path) : null,
            'items' => $order->items->map(fn ($i) => [
                'name' => $i->displayName(),
                'quantity' => $i->quantity,
                'source' => $i->sourceLabel(),
                'source_type' => $i->source_type,
            ])->values(),
            'timestamps' => [
                'placed_at' => optional($order->placed_at)->toIso8601String(),
                'assigned_at' => optional($order->assigned_at)->toIso8601String(),
                'picked_up_at' => optional($order->picked_up_at)->toIso8601String(),
                'out_for_delivery_at' => optional($order->out_for_delivery_at)->toIso8601String(),
                'delivered_at' => optional($order->delivered_at)->toIso8601String(),
                'failed_at' => optional($order->failed_at)->toIso8601String(),
                'canceled_at' => optional($order->canceled_at)->toIso8601String(),
            ],
            'timeline' => $this->delivery->customerTimeline($order),
            'status_steps' => $this->delivery->customerStatusSteps($order),
            'logistics' => $this->delivery->logisticsChecklist($order),
            'where_now' => $this->delivery->customerStatusLabel($order->status),
            'payment' => $order->payment ? [
                'method' => $order->payment->payment_method,
                'method_label' => $order->payment->methodLabel(),
                'status' => $order->payment->status,
                'icon' => config('payments.icons.'.$order->payment->payment_method),
                'brand' => config('payments.brand.'.$order->payment->payment_method),
                'transfer_code' => $order->payment->transferCode(),
                'needs_receipt' => $order->payment->needsReceipt(),
                'allows_receipt' => $order->payment->allowsReceipt(),
                'needs_transfer_code' => $order->payment->needsTransferCode(),
                'has_receipt' => (bool) $order->payment->receipt_path,
                'receipt_url' => $order->payment->receipt_path
                    ? Storage::disk('public')->url($order->payment->receipt_path)
                    : null,
                'account_hint' => config('payments.accounts.'.$order->payment->payment_method),
                'instructions' => str_replace(
                    '{amount}',
                    number_format((float) $order->total * app(\App\Services\CurrencyService::class)->currentAedToSypRate()).' ل.س',
                    config('payments.instructions.'.$order->payment->payment_method, '')
                ),
            ] : null,
        ]);
    }

    public function cancel(Request $request, Order $order)
    {
        $this->authorize('view', $order);

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|min:3|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (! $this->delivery->canCustomerCancel($order)) {
            return response()->json([
                'message' => 'لا يمكن إلغاء هذا الطلب بعد مرحلة الشحن.',
                'cancel_policy' => $this->delivery->customerCancelPolicy($order),
            ], 422);
        }

        $reason = trim($request->input('reason'));
        $this->delivery->transition($order, 'cancelled', $request->user(), $reason, [
            'customer_visible' => true,
            'cancelled_by' => 'customer',
        ]);
        $order->update(['cancellation_reason' => $reason]);

        return response()->json([
            'ok' => true,
            'message' => 'تم إلغاء الطلب.',
            'order' => $this->serializeOrder(
                $order->fresh(['items.product', 'payment', 'deliveryEvents', 'driver']),
                includeOtp: false
            ),
        ]);
    }

    public function reorder(Request $request, Order $order)
    {
        $this->authorize('view', $order);
        $order->load(['items.product', 'items.variant']);

        $added = [];
        $skipped = [];

        foreach ($order->items as $item) {
            if (! $item->product_id || $item->source_type === OrderItem::SOURCE_EXTERNAL) {
                $skipped[] = [
                    'name' => $item->displayName(),
                    'reason' => 'منتج خارجي — أضيفيه يدوياً من الرابط.',
                    'external_url' => $item->external_url,
                ];
                continue;
            }

            $product = $item->product;
            if (! $product) {
                $skipped[] = ['name' => $item->displayName(), 'reason' => 'المنتج غير متوفر.'];
                continue;
            }

            $variant = $item->variant;
            $unit = $variant
                ? (float) ($variant->sale_price ?: $variant->price)
                : (float) ($product->sale_price ?: $product->price);

            $cartItem = $request->user()->cartItems()->updateOrCreate([
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'saved_for_later' => false,
            ], [
                'quantity' => max(1, (int) $item->quantity),
                'price_snapshot' => $unit,
                'saved_for_later' => false,
            ]);

            $added[] = [
                'cart_item_id' => $cartItem->id,
                'name' => $item->displayName(),
                'quantity' => $cartItem->quantity,
            ];
        }

        return response()->json([
            'ok' => true,
            'added_count' => count($added),
            'added' => $added,
            'skipped' => $skipped,
            'cart_url' => url('/cart'),
            'message' => count($added)
                ? 'تمت إضافة المنتجات للسلة.'
                : 'ما في منتجات محلية لإعادة الطلب.',
        ]);
    }

    public function invoice(Request $request, Order $order)
    {
        $this->authorize('view', $order);
        $order->load(['items.product', 'items.platform', 'items.variant', 'payment', 'shippingAddress', 'user']);

        $rate = app(\App\Services\CurrencyService::class)->currentAedToSypRate();

        return response()->json([
            'invoice_number' => 'INV-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            'order_id' => $order->id,
            'issued_at' => now()->toIso8601String(),
            'placed_at' => optional($order->placed_at ?? $order->created_at)->toIso8601String(),
            'status' => $order->status,
            'status_label' => $this->delivery->customerStatusLabel($order->status),
            'customer' => [
                'name' => $order->user?->name,
                'email' => $order->user?->email,
                'phone' => $order->user?->phone,
            ],
            'shipping_address' => $order->shippingAddress ? [
                'recipient_name' => $order->shippingAddress->recipient_name,
                'phone' => $order->shippingAddress->phone,
                'street_address' => $order->shippingAddress->street_address,
                'city' => $order->shippingAddress->city,
                'state' => $order->shippingAddress->state,
                'country' => $order->shippingAddress->country,
            ] : null,
            'items' => $order->items->map(fn ($i) => [
                'name' => $i->displayName(),
                'quantity' => $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'line_total' => (float) $i->line_total,
                'source' => $i->sourceLabel(),
            ])->values(),
            'totals' => [
                'subtotal' => (float) $order->subtotal,
                'shipping_cost' => (float) $order->shipping_cost,
                'discount_amount' => (float) $order->discount_amount,
                'wallet_amount' => (float) ($order->wallet_amount ?? 0),
                'total' => (float) $order->total,
                'total_syp' => (int) round((float) $order->total * $rate),
                'currency' => 'AED',
            ],
            'coupon_code' => $order->coupon_code,
            'payment' => $order->payment ? [
                'method' => $order->payment->payment_method,
                'method_label' => $order->payment->methodLabel(),
                'status' => $order->payment->status,
            ] : null,
            'is_gift' => (bool) $order->is_gift,
            'gift_message' => $order->gift_message,
            'customer_notes' => $order->customer_notes,
            'print_url' => url('/orders/'.$order->id.'/invoice'),
        ]);
    }

    public function verifyOtp(Request $request, Order $order)
    {
        $this->authorize('view', $order);
        $request->validate(['otp' => 'required|string']);
        $this->delivery->verifyOtp($order, $request->otp, $request->user());

        return response()->json(['message' => 'تم التحقق من رمز الاستلام.', 'otp_verified' => true]);
    }

    public function uploadReceipt(Request $request, Order $order)
    {
        $this->authorize('view', $order);

        $payment = $order->payment;
        if (! $payment) {
            return response()->json(['message' => 'لا يوجد سجل دفع لهذا الطلب.'], 422);
        }

        if ($payment->status === 'paid') {
            return response()->json(['message' => 'الدفع مؤكَّد مسبقاً.'], 422);
        }

        $request->validate([
            'receipt' => 'nullable|image|max:5120',
            'transfer_code' => 'nullable|string|max:120',
        ]);

        if (! $request->hasFile('receipt') && ! trim((string) $request->input('transfer_code'))) {
            return response()->json(['message' => 'ارفع الوصل أو أدخل الكود.'], 422);
        }

        $data = [];
        if ($request->hasFile('receipt')) {
            if (! $payment->allowsReceipt()) {
                return response()->json(['message' => 'طريقة الدفع هذه لا تستخدم إيصال صورة.'], 422);
            }
            $data['receipt_path'] = $request->file('receipt')->store('receipts/orders/'.date('Y/m'), 'public');
            $data['receipt_uploaded_at'] = now();
        }
        if ($code = trim((string) $request->input('transfer_code'))) {
            $data['transaction_id'] = $code;
            $data['metadata'] = array_merge($payment->metadata ?? [], [
                'transfer_code' => $code,
                'proof_updated_by' => $request->user()->id,
            ]);
        }

        $payment->update($data);
        $freshPayment = $payment->fresh();
        app(\App\Services\Orders\OrderAlertService::class)->pushAdmin(
            'payment_receipt',
            "إثبات دفع لطلب #{$order->id}",
            ($request->user()->name ?: 'زبون').' أرسل إثبات دفع — بانتظار التأكيد',
            "وصل إثبات دفع للطلب رقم {$order->id}",
            route('admin.orders.show', $order),
            ['order_id' => $order->id]
        );

        return response()->json([
            'ok' => true,
            'receipt_url' => $freshPayment->receipt_path
                ? Storage::disk('public')->url($freshPayment->receipt_path)
                : null,
            'transfer_code' => $freshPayment->transferCode(),
            'message' => 'تم حفظ الإثبات. رح نأكّد الدفع من الداشبورد.',
        ]);
    }

    public function store(Request $request)
    {
        if (is_string($request->input('external_items'))) {
            $decoded = json_decode($request->input('external_items'), true);
            $request->merge(['external_items' => is_array($decoded) ? $decoded : []]);
        }

        $validator = Validator::make($request->all(), [
            'shipping_address_id' => 'required|exists:addresses,id',
            'billing_address_id' => 'nullable|exists:addresses,id',
            'payment_method' => 'required|string|in:'.implode(',', array_keys(config('payments.methods', []))),
            'transfer_code' => 'nullable|string|max:120',
            'receipt' => 'nullable|image|max:5120',
            'external_items' => 'nullable|array',
            'external_items.*.product_name' => 'required_with:external_items|string|max:500',
            'external_items.*.quantity' => 'required_with:external_items|integer|min:1',
            'external_items.*.unit_price' => 'nullable|numeric|min:0',
            'external_items.*.platform_id' => 'nullable|exists:external_platforms,id',
            'external_items.*.external_url' => 'nullable|string|max:2048',
            'external_items.*.external_product_id' => 'nullable|string|max:191',
            'external_items.*.variant_data' => 'nullable|array',
            'external_items.*.image' => 'nullable|string|max:2048',
            'shipping_method' => 'nullable|string|max:40',
            'coupon_code' => 'nullable|string|max:60',
            'customer_notes' => 'nullable|string|max:2000',
            'wallet_amount' => 'nullable|numeric|min:0',
            'points_used' => 'nullable|integer|min:0',
            'is_gift' => 'nullable|boolean',
            'gift_wrapping' => 'nullable|boolean',
            'gift_message' => 'nullable|string|max:1000',
            'gift_recipient_name' => 'nullable|string|max:255',
            'gift_recipient_phone' => 'nullable|string|max:50',
            'gift_recipient_address' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $paymentMethod = $request->payment_method;
        $transferCode = trim((string) $request->input('transfer_code', ''));
        $hasReceipt = $request->hasFile('receipt');
        if ($proofError = Payment::validateProof($paymentMethod, $transferCode, $hasReceipt)) {
            return response()->json(['message' => $proofError, 'errors' => ['payment' => [$proofError]]], 422);
        }
        $address = Address::where('id', $request->shipping_address_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $address) {
            return response()->json(['message' => 'Shipping address not found'], 404);
        }

        if ($address->latitude === null || $address->longitude === null) {
            return response()->json([
                'message' => 'حدّدي موقع التوصيل على الخريطة قبل تأكيد الطلب.',
                'code' => 'address_missing_coordinates',
            ], 422);
        }

        $cartItems = $request->user()->cartItems()
            ->where('saved_for_later', false)
            ->with(['product.vendor', 'variant'])
            ->get();
        $externalRaw = $request->input('external_items', []);
        if (is_string($externalRaw)) {
            $externalRaw = json_decode($externalRaw, true) ?: [];
        }
        $externalItems = collect($externalRaw);

        if ($cartItems->isEmpty() && $externalItems->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty'], 422);
        }

        $order = DB::transaction(function () use ($request, $cartItems, $externalItems, $address) {
            $localSubtotal = $cartItems->sum(function ($item) {
                $unitPrice = $item->variant?->sale_price ?? $item->variant?->price ?? $item->product->sale_price ?? $item->product->price;

                return $unitPrice * $item->quantity;
            });

            $externalSubtotal = $externalItems->sum(function ($item) {
                return ((float) ($item['unit_price'] ?? 0)) * (int) ($item['quantity'] ?? 1);
            });

            $subtotal = $localSubtotal + $externalSubtotal;

            $shippingMethod = $request->input('shipping_method', 'standard');
            $shippingCfg = config('shipping.methods.'.$shippingMethod) ?: config('shipping.methods.standard');
            $shippingCost = (float) ($shippingCfg['fee'] ?? 0);

            $isGift = $request->boolean('is_gift');
            $giftWrapping = $isGift && $request->boolean('gift_wrapping');
            $giftFee = $giftWrapping ? (float) config('shipping.gift_wrapping_fee', 0) : 0;

            $coupon = null;
            $discount = 0.0;
            if ($request->filled('coupon_code')) {
                $couponService = app(\App\Services\Commerce\CouponService::class);
                $preview = $couponService->preview($request->input('coupon_code'), (float) $subtotal);
                $discount = (float) $preview['discount'];
                $coupon = \App\Models\Coupon::query()->whereRaw('UPPER(code) = ?', [mb_strtoupper(trim($request->input('coupon_code')))])->first();
            }

            $pointsUsed = (int) $request->input('points_used', 0);
            $walletAmount = (float) $request->input('wallet_amount', 0);
            // Points = wallet credits in SYP (1 point = 1 SYP of store credit)
            $creditWantedSyp = (int) round($walletAmount + $pointsUsed * (float) config('shipping.points_to_syp', 1));

            $vendorId = $cartItems->first()?->product?->vendor_id
                ?? Vendor::query()->value('id');

            if (! $vendorId) {
                abort(422, 'لا يوجد متجر محلي مرتبط لإنشاء الطلب.');
            }

            $hasLocal = $cartItems->isNotEmpty();
            $hasExternal = $externalItems->isNotEmpty();
            $sourceType = $hasLocal && $hasExternal ? 'mixed' : ($hasExternal ? 'external' : 'local');

            $paymentMethod = $request->payment_method;
            $transferCode = trim((string) $request->input('transfer_code', ''));
            $rules = config('payments.rules.'.$paymentMethod, []);
            $paymentStatus = 'pending';
            $initialStatus = 'pending';
            $amountSyp = null;

            $totalBeforeCredit = max(0, $subtotal + $shippingCost + $giftFee - $discount);

            // كاش عند الاستلام / رصيد متجر → بدء التجهيز مباشرة
            if (! empty($rules['start_preparing']) || in_array($paymentMethod, config('payments.auto_confirm', []), true)) {
                $initialStatus = 'preparing';
            }

            $rate = app(\App\Services\CurrencyService::class)->currentAedToSypRate();
            $totalSyp = (int) round($totalBeforeCredit * $rate);

            if ($creditWantedSyp > 0) {
                $creditWantedSyp = min($creditWantedSyp, $totalSyp, (int) $request->user()->store_credit_syp);
            }

            if ($paymentMethod === 'store_credit') {
                $amountSyp = max(0, $totalSyp - $creditWantedSyp);
                // If using store_credit as primary method, debit full remainder from credit
                $need = $totalSyp;
                if ((int) $request->user()->store_credit_syp < $need) {
                    abort(422, 'رصيد المتجر غير كافٍ.');
                }
                $creditWantedSyp = $need;
                $paymentStatus = 'paid';
                $initialStatus = 'preparing';
            }

            // COD: الدفع كامل عند التسليم — الطلب يتجهّز والدفع يبقى pending حتى التسليم
            if ($paymentMethod === 'cash_on_delivery') {
                $paymentStatus = 'pending';
                $initialStatus = 'preparing';
            }

            $order = Order::create([
                'user_id' => $request->user()->id,
                'vendor_id' => $vendorId,
                'source_type' => $sourceType,
                'status' => $initialStatus,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost + $giftFee,
                'tax_amount' => 0,
                'discount_amount' => $discount,
                'wallet_amount' => $creditWantedSyp / max($rate, 0.0001),
                'points_used' => $pointsUsed,
                'total' => max(0, $totalBeforeCredit - ($creditWantedSyp / max($rate, 0.0001))),
                'shipping_address_id' => $address->id,
                'billing_address_id' => $request->billing_address_id,
                'shipping_method' => $shippingMethod,
                'customer_notes' => $request->input('customer_notes'),
                'is_gift' => $isGift,
                'gift_wrapping' => $giftWrapping,
                'gift_message' => $isGift ? $request->input('gift_message') : null,
                'gift_recipient_name' => $isGift ? $request->input('gift_recipient_name') : null,
                'gift_recipient_phone' => $isGift ? $request->input('gift_recipient_phone') : null,
                'gift_recipient_address' => $isGift ? $request->input('gift_recipient_address') : null,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'tracking_number' => null,
                'placed_at' => now(),
            ]);

            if ($creditWantedSyp > 0) {
                $request->user()->debitStoreCredit(
                    $creditWantedSyp,
                    $paymentMethod === 'store_credit' ? 'order_payment' : 'order_wallet',
                    $order,
                    'خصم رصيد/نقاط لطلبية #'.$order->id
                );
                if ($paymentMethod !== 'store_credit' && $creditWantedSyp >= $totalSyp) {
                    $paymentStatus = 'paid';
                    $initialStatus = 'preparing';
                    $order->update(['status' => $initialStatus]);
                }
            }

            if ($coupon && $discount > 0) {
                $coupon->increment('used_count');
                \App\Models\CouponRedemption::query()->create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $request->user()->id,
                    'order_id' => $order->id,
                    'discount_amount' => $discount,
                ]);
            }

            if ($paymentMethod === 'store_credit') {
                // already debited above as full credit
            }

            if ($initialStatus === 'preparing') {
                $order->update([
                    'tracking_number' => $this->delivery->generateTrackingNumber($order),
                ]);
            }

            foreach ($cartItems as $item) {
                $unitPrice = $item->variant?->sale_price ?? $item->variant?->price ?? $item->product->sale_price ?? $item->product->price;
                OrderItem::create([
                    'order_id' => $order->id,
                    'source_type' => OrderItem::SOURCE_LOCAL,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->product?->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $item->quantity,
                ]);
            }

            // Group external lines by platform → one PurchaseRequest per platform, linked to this Order
            $groupedExternal = $externalItems->groupBy(fn ($i) => (string) ($i['platform_id'] ?? 'none'));

            foreach ($groupedExternal as $platformKey => $lines) {
                $platformId = $platformKey !== 'none' ? (int) $platformKey : null;
                $firstUrl = $lines->first()['external_url'] ?? null;

                $pr = PurchaseRequest::create([
                    'customer_id' => $request->user()->id,
                    'order_id' => $order->id,
                    'platform_id' => $platformId,
                    'url' => $firstUrl,
                    'customer_notes' => 'جزء من الطلبية #'.$order->id,
                    'status' => PurchaseRequest::STATUS_PENDING,
                ]);

                foreach ($lines as $line) {
                    $qty = (int) ($line['quantity'] ?? 1);
                    $unit = (float) ($line['unit_price'] ?? 0);
                    $variantData = $line['variant_data'] ?? [];
                    if (! empty($line['image'])) {
                        $variantData['image'] = $line['image'];
                    }

                    $prItem = $pr->items()->create([
                        'product_name' => $line['product_name'],
                        'external_product_id' => $line['external_product_id'] ?? null,
                        'variant_data' => $variantData ?: null,
                        'quantity' => $qty,
                    ]);

                    OrderItem::create([
                        'order_id' => $order->id,
                        'source_type' => OrderItem::SOURCE_EXTERNAL,
                        'platform_id' => $platformId,
                        'product_id' => null,
                        'product_name' => $line['product_name'],
                        'external_url' => $line['external_url'] ?? null,
                        'purchase_request_item_id' => $prItem->id,
                        'quantity' => $qty,
                        'unit_price' => $unit,
                        'line_total' => $unit * $qty,
                        'metadata' => [
                            'quote' => $variantData['quote'] ?? null,
                            'external_product_id' => $line['external_product_id'] ?? null,
                            'image' => $line['image'] ?? ($variantData['image'] ?? null),
                        ],
                    ]);
                }
            }

            $receiptPath = null;
            if ($request->hasFile('receipt')) {
                $receiptPath = $request->file('receipt')->store('receipts/orders/'.date('Y/m'), 'public');
            }

            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $paymentMethod,
                'transaction_id' => $transferCode !== '' ? $transferCode : null,
                'amount' => $subtotal,
                'status' => $paymentStatus,
                'paid_at' => $paymentStatus === 'paid' ? now() : null,
                'receipt_path' => $receiptPath,
                'receipt_uploaded_at' => $receiptPath ? now() : null,
                'metadata' => array_filter([
                    'transfer_code' => $transferCode !== '' ? $transferCode : null,
                    'awaiting_admin_confirm' => ! empty($rules['admin_confirm']),
                    'pay_at_delivery' => $paymentMethod === 'cash_on_delivery',
                    'pay_at_delivery_note' => $paymentMethod === 'cash_on_delivery' ? 'الدفع كامل عند التسليم' : null,
                    'amount_syp' => $amountSyp,
                    'account_hint' => config('payments.accounts.'.$paymentMethod),
                ]),
            ]);
            $request->user()->cartItems()->where('saved_for_later', false)->delete();

            $this->delivery->ensureInitialEvent($order);
            $fresh = $order->fresh(['user', 'shippingAddress', 'items.platform', 'payment']);
            app(CustomerOrderNotifier::class)->notifyLocalOrderCreated($fresh);
            app(\App\Services\Orders\OrderAlertService::class)->alertAdminsNewOrder($fresh);
            app(\App\Services\Orders\OrderAlertService::class)->notifyCustomerInApp(
                $fresh,
                $fresh->status,
                $fresh->status === 'preparing' ? 'بدأنا التحضير' : 'منؤكّد الدفع ونبلّش التجهيز'
            );

            return $fresh->load(['items.product', 'items.platform', 'payment', 'purchaseRequests.platform']);
        });

        return response()->json(['orders' => [$order], 'order' => $order], 201);
    }

    private function serializeOrder(Order $order, bool $includeOtp = false): array
    {
        $data = $order->toArray();
        $data['status_label'] = $this->delivery->customerStatusLabel($order->status);
        $data['status_key'] = $this->delivery->customerStatusKey($order->status);
        $data['sources'] = $order->sourcesLabel();
        $data['track_url'] = url('/orders/'.$order->id.'/track');
        $data['items'] = $order->items->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->displayName(),
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
                'source' => $item->sourceLabel(),
                'source_type' => $item->source_type,
                'external_url' => $item->external_url,
                'product' => $item->product ? ['id' => $item->product->id, 'name' => $item->product->name] : null,
            ];
        })->values();

        if ($includeOtp) {
            $data['delivery_otp'] = $order->getAttributes()['delivery_otp'] ?? null;
            $data['otp_verified'] = (bool) $order->delivery_otp_verified_at;
        }

        $data['timeline'] = $this->delivery->customerTimeline($order);
        $data['can_cancel'] = $this->delivery->canCustomerCancel($order);
        $data['cancel_policy'] = $this->delivery->customerCancelPolicy($order);
        $data['can_reorder'] = $order->items->contains(fn ($i) => (bool) $i->product_id);
        $data['invoice_url'] = url('/orders/'.$order->id.'/invoice');
        $data['cancellation_reason'] = $order->cancellation_reason;

        return $data;
    }
}
