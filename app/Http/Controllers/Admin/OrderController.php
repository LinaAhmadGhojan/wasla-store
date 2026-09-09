<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\Delivery\OrderDeliveryService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderDeliveryService $delivery)
    {
    }

    public function index(Request $request, CurrencyService $currency)
    {
        $query = Order::with(['user', 'payment', 'driver'])
            ->orderByDesc('placed_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($pm = $request->query('payment')) {
            $query->whereHas('payment', fn ($q) => $q->where('payment_method', $pm));
        }

        return view('admin.orders.index', [
            'orders' => $query->paginate(20)->withQueryString(),
            'statuses' => $this->delivery->adminSelectableStatuses(),
            'paymentMethods' => config('payments.methods'),
            'exchangeRate' => $currency->currentAedToSypRate(),
        ]);
    }

    public function show(Order $order, CurrencyService $currency)
    {
        $order->load(['user', 'items.product', 'items.variant', 'items.platform', 'payment', 'shippingAddress', 'driver', 'deliveryEvents.actor', 'purchaseRequests.platform', 'purchaseRequests.items']);
        $this->delivery->ensureInitialEvent($order);

        $drivers = $this->driverQuery()->orderBy('name')->get();
        $order = $order->fresh(['deliveryEvents.actor', 'driver', 'items.product', 'items.variant', 'items.platform', 'payment', 'shippingAddress', 'user', 'purchaseRequests.platform', 'purchaseRequests.items']);

        return view('admin.orders.show', [
            'order' => $order,
            'statuses' => $this->delivery->adminSelectableStatuses(),
            'customerStatusLabel' => $this->delivery->customerStatusLabel($order->status),
            'customerStatusSteps' => $this->delivery->customerStatusSteps($order),
            'nextStatuses' => array_values(array_filter(
                $this->delivery->allowedNext($order->status),
                fn ($k) => $k !== 'confirmed'
            )),
            'logisticsChecklist' => $this->delivery->logisticsChecklist($order),
            'failureReasons' => OrderDeliveryService::FAILURE_REASONS,
            'paymentMethods' => config('payments.methods'),
            'exchangeRate' => $currency->currentAedToSypRate(),
            'drivers' => $drivers,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|string',
            'note' => 'nullable|string|max:500',
            'failure_reason' => 'nullable|string',
        ]);

        $status = $request->string('status')->toString();

        if ($status === 'failed_delivery') {
            $request->validate(['failure_reason' => 'required|string']);
            $this->delivery->markFailed($order, $request->failure_reason, $request->note, $request->user());
        } else {
            $this->delivery->transition($order, $status, $request->user(), $request->note);
        }

        return back()->with('success', 'تم تحديث حالة الطلب.');
    }

    public function assignDriver(Request $request, Order $order)
    {
        $request->validate([
            'driver_id' => 'required|exists:users,id',
        ]);

        $driver = User::query()->findOrFail($request->driver_id);
        $this->delivery->assignDriver($order, $driver, $request->user());

        return back()->with('success', 'تم تسجيل إسناد المندوب داخلياً (ما يغيّر حالة الزبون).');
    }

    public function addLogistics(Request $request, Order $order)
    {
        $request->validate([
            'logistics' => 'required|string',
            'note' => 'nullable|string|max:500',
        ]);

        $this->delivery->addLogisticsNote($order, $request->logistics, $request->user(), $request->note);

        return back()->with('success', 'تم تسجيل المرحلة اللوجستية — ظاهرة للزبون.');
    }

    public function regenerateOtp(Request $request, Order $order)
    {
        if (! in_array($order->status, ['shipped', 'out_for_delivery', 'assigned', 'picked_up', 'failed_delivery'], true)) {
            return back()->withErrors(['otp' => 'لا يمكن إعادة توليد الرمز في هذه الحالة.']);
        }

        $otp = $this->delivery->generateOtp();
        $order->update([
            'delivery_otp' => $otp,
            'delivery_otp_verified_at' => null,
            'delivery_otp_attempts' => 0,
        ]);
        $this->delivery->record($order, $order->status, 'إعادة توليد رمز الاستلام', $request->user(), [
            'customer_visible' => false,
        ]);

        return back()->with('success', 'تم توليد رمز استلام جديد.');
    }

    public function verifyOtp(Request $request, Order $order)
    {
        $request->validate(['otp' => 'required|string']);
        $this->delivery->verifyOtp($order, $request->otp, $request->user());

        return back()->with('success', 'تم التحقق من رمز الاستلام.');
    }

    public function markDelivered(Request $request, Order $order)
    {
        $request->validate([
            'otp' => 'nullable|string',
            'signature' => 'nullable|image|max:4096',
            'proof_photo' => 'nullable|image|max:6144',
        ]);

        $this->delivery->markDelivered(
            $order,
            $request->otp,
            $request->file('signature'),
            $request->file('proof_photo'),
            $request->user(),
            requireOtp: true
        );

        return back()->with('success', 'تم تسليم الطلب بنجاح.');
    }

    public function markFailed(Request $request, Order $order)
    {
        $request->validate([
            'failure_reason' => 'required|string',
            'note' => 'nullable|string|max:500',
        ]);

        $this->delivery->markFailed($order, $request->failure_reason, $request->note, $request->user());

        return back()->with('success', 'تم تسجيل تعذّر التسليم.');
    }

    public function confirmPayment(Order $order)
    {
        $order->payment?->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        $this->delivery->ensureInitialEvent($order);

        // تأكيد الدفع = بدء التجهيز (مثل SHEIN بعد الدفع)
        if (in_array($order->status, ['pending', 'confirmed'], true)) {
            $this->delivery->transition($order, 'preparing', auth()->user(), 'تأكيد الدفع — بدء التجهيز');
        }

        return back()->with('success', 'تم تأكيد الدفع وبدء التجهيز.');
    }

    private function driverQuery()
    {
        $query = User::query();
        $driverRoleId = Role::query()->where('name', 'driver')->value('id');
        if ($driverRoleId) {
            $query->where('role_id', $driverRoleId);
        }

        return $query;
    }
}
