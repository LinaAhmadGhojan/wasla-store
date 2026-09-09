<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ExternalShopping\ConfirmPurchaseRequestPaymentAction;
use App\Actions\ExternalShopping\QuotePurchaseRequestAction;
use App\Actions\ExternalShopping\UpdatePurchaseRequestStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuotePurchaseRequestRequest;
use App\Http\Requests\Admin\UpdatePurchaseRequestStatusRequest;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestPayment;
use App\Services\CurrencyService;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    public function index(Request $request, CurrencyService $currency)
    {
        $query = PurchaseRequest::with(['customer', 'platform'])->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('admin.purchase-requests.index', [
            'purchaseRequests' => $query->paginate(15)->withQueryString(),
            'statuses' => PurchaseRequest::STATUSES,
            'statusLabels' => PurchaseRequest::STATUS_LABELS,
            'currentStatus' => $status,
            'currency' => $currency,
            'exchangeRate' => $currency->currentAedToSypRate(),
        ]);
    }

    public function show(PurchaseRequest $purchaseRequest, CurrencyService $currency)
    {
        return view('admin.purchase-requests.show', [
            'purchaseRequest' => $purchaseRequest->load(['customer', 'platform', 'items.externalProduct', 'latestPayment', 'procurementBatch']),
            'statuses' => PurchaseRequest::STATUSES,
            'statusLabels' => PurchaseRequest::STATUS_LABELS,
            'currency' => $currency,
            'exchangeRate' => $currency->currentAedToSypRate(),
        ]);
    }

    public function quote(QuotePurchaseRequestRequest $request, PurchaseRequest $purchaseRequest, QuotePurchaseRequestAction $action)
    {
        $action->execute($purchaseRequest, $request->validated());

        return redirect()->route('admin.purchase-requests.show', $purchaseRequest)->with('success', 'Quote saved successfully.');
    }

    public function updateStatus(UpdatePurchaseRequestStatusRequest $request, PurchaseRequest $purchaseRequest, UpdatePurchaseRequestStatusAction $action)
    {
        $action->execute($purchaseRequest, $request->validated()['status']);

        return redirect()->route('admin.purchase-requests.show', $purchaseRequest)->with('success', 'Status updated successfully.');
    }

    public function confirmPayment(PurchaseRequest $purchaseRequest, ConfirmPurchaseRequestPaymentAction $action)
    {
        $payment = $purchaseRequest->latestPayment;

        if (! $payment || $payment->status !== PurchaseRequestPayment::STATUS_SUBMITTED) {
            return back()->with('error', 'لا توجد دفعة بانتظار التأكيد.');
        }

        $action->execute($payment, auth()->user());

        return back()->with('success', 'تم تأكيد الدفع — الطلب أصبح «مدفوع» ويمكن إضافته لدفعة شراء.');
    }

    public function rejectPayment(PurchaseRequest $purchaseRequest)
    {
        $payment = $purchaseRequest->latestPayment;

        if (! $payment || $payment->status !== PurchaseRequestPayment::STATUS_SUBMITTED) {
            return back()->with('error', 'لا توجد دفعة بانتظار التأكيد.');
        }

        $payment->update([
            'status'     => PurchaseRequestPayment::STATUS_REJECTED,
            'admin_notes' => 'رُفض بواسطة '.auth()->user()->name.' في '.now()->format('Y-m-d H:i'),
        ]);

        return back()->with('success', 'تم رفض الدفعة — يمكن للعميل إرسال دفعة جديدة.');
    }
}
