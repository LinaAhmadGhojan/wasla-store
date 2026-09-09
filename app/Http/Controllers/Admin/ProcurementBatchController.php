<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProcurementBatch;
use App\Models\PurchaseRequest;
use Illuminate\Http\Request;

class ProcurementBatchController extends Controller
{
    public function index()
    {
        return view('admin.procurement-batches.index', [
            'batches' => ProcurementBatch::withCount('purchaseRequests')
                ->orderByDesc('created_at')
                ->paginate(15),
        ]);
    }

    public function create()
    {
        return view('admin.procurement-batches.create', [
            'batch' => new ProcurementBatch(['status' => ProcurementBatch::STATUS_OPEN]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $batch = ProcurementBatch::create([
            'reference' => ProcurementBatch::nextReference(),
            'title' => $data['title'],
            'notes' => $data['notes'] ?? null,
            'status' => ProcurementBatch::STATUS_OPEN,
        ]);

        return redirect()
            ->route('admin.procurement-batches.show', $batch)
            ->with('success', 'تم إنشاء دفعة الشراء «'.$batch->reference.'»');
    }

    public function show(ProcurementBatch $procurementBatch)
    {
        $procurementBatch->load(['purchaseRequests.customer', 'purchaseRequests.platform']);

        $available = PurchaseRequest::with(['customer', 'platform'])
            ->whereNull('procurement_batch_id')
            ->whereIn('status', [
                PurchaseRequest::STATUS_CUSTOMER_APPROVED,
                PurchaseRequest::STATUS_PAID,
            ])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.procurement-batches.show', [
            'batch' => $procurementBatch,
            'availableRequests' => $available,
            'statusLabels' => ProcurementBatch::STATUS_LABELS,
            'prStatusLabels' => PurchaseRequest::STATUS_LABELS,
        ]);
    }

    public function update(Request $request, ProcurementBatch $procurementBatch)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'required|in:'.implode(',', ProcurementBatch::STATUSES),
            'total_aed' => 'nullable|numeric|min:0',
            'external_order_ref' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $updates = [
            'title' => $data['title'],
            'status' => $data['status'],
            'total_aed' => $data['total_aed'] ?? null,
            'external_order_ref' => $data['external_order_ref'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        if ($data['status'] === ProcurementBatch::STATUS_PURCHASED && ! $procurementBatch->purchased_at) {
            $updates['purchased_at'] = now();
        }

        $procurementBatch->update($updates);

        if ($data['status'] === ProcurementBatch::STATUS_PURCHASING) {
            $procurementBatch->purchaseRequests()->update(['status' => PurchaseRequest::STATUS_PURCHASING]);
        }

        if (in_array($data['status'], [ProcurementBatch::STATUS_PURCHASED, ProcurementBatch::STATUS_SHIPPED], true)) {
            $procurementBatch->purchaseRequests()->update(['status' => PurchaseRequest::STATUS_PURCHASED]);
        }

        return redirect()
            ->route('admin.procurement-batches.show', $procurementBatch)
            ->with('success', 'تم تحديث الدفعة.');
    }

    public function addRequest(ProcurementBatch $procurementBatch, PurchaseRequest $purchaseRequest)
    {
        if ($procurementBatch->status !== ProcurementBatch::STATUS_OPEN) {
            return back()->with('error', 'الدفعة مغلقة — لا يمكن إضافة طلبات.');
        }

        if (! $purchaseRequest->canBeAddedToBatch()) {
            return back()->with('error', 'هذا الطلب غير متاح للإضافة (يجب موافقة/دفع وبدون دفعة أخرى).');
        }

        $purchaseRequest->update(['procurement_batch_id' => $procurementBatch->id]);

        return back()->with('success', 'تم إضافة طلب #'.$purchaseRequest->id.' للدفعة.');
    }

    public function removeRequest(ProcurementBatch $procurementBatch, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->procurement_batch_id !== $procurementBatch->id) {
            return back()->with('error', 'الطلب ليس في هذه الدفعة.');
        }

        if ($procurementBatch->status !== ProcurementBatch::STATUS_OPEN) {
            return back()->with('error', 'لا يمكن إزالة طلبات بعد بدء الشراء.');
        }

        $purchaseRequest->update(['procurement_batch_id' => null]);

        return back()->with('success', 'تم إزالة الطلب من الدفعة.');
    }
}
