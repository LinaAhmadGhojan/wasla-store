@extends('admin.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">{{ $batch->reference }}</h1>
            <p class="text-muted mb-0">{{ $batch->title }} · {{ $batch->statusLabel() }}</p>
        </div>
        <a href="{{ route('admin.procurement-batches.index') }}" class="btn btn-outline-secondary">رجوع</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase mb-3">إعدادات الدفعة</h2>
                    <form action="{{ route('admin.procurement-batches.update', $batch) }}" method="post">
                        @csrf
                        @method('patch')
                        <div class="mb-3">
                            <label class="form-label">العنوان</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $batch->title) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select">
                                @foreach($statusLabels as $key => $label)
                                    <option value="{{ $key }}" @selected(old('status', $batch->status) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">المبلغ الفعلي (د.إ)</label>
                            <input type="number" step="0.01" name="total_aed" class="form-control" dir="ltr"
                                value="{{ old('total_aed', $batch->total_aed ?? $batch->totalAedFromRequests()) }}">
                            <div class="form-text">مجموع الطلبات: {{ number_format($batch->totalAedFromRequests(), 2) }} د.إ</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">رقم طلب SHEIN/Noon</label>
                            <input type="text" name="external_order_ref" class="form-control" dir="ltr"
                                value="{{ old('external_order_ref', $batch->external_order_ref) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ملاحظات</label>
                            <textarea name="notes" rows="2" class="form-control">{{ old('notes', $batch->notes) }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">حفظ</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase mb-3">طلبات في الدفعة ({{ $batch->purchaseRequests->count() }})</h2>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Order</th>
                                    <th>الحالة</th>
                                    <th>د.إ</th>
                                    <th>ل.س</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($batch->purchaseRequests as $pr)
                                    <tr>
                                        <td><a href="{{ route('admin.purchase-requests.show', $pr) }}">#{{ $pr->id }}</a></td>
                                        <td>{{ $pr->customer?->name }}</td>
                                        <td>{{ $prStatusLabels[$pr->status] ?? $pr->status }}</td>
                                        <td dir="ltr">{{ $pr->final_price ? number_format($pr->final_price, 2) : '—' }}</td>
                                        <td>{{ $pr->final_price_syp ? number_format($pr->final_price_syp).' ل.س' : '—' }}</td>
                                        <td class="text-end">
                                            @if($batch->status === 'open')
                                                <form action="{{ route('admin.procurement-batches.remove-request', [$batch, $pr]) }}" method="post" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">إزالة</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-3">لا طلبات — أضيفي من القائمة بالأسفل.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($batch->status === 'open' && $availableRequests->count())
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="h6 text-muted text-uppercase mb-3">طلبات متاحة للإضافة</h2>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Order</th>
                                        <th>الحالة</th>
                                        <th>ل.س</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($availableRequests as $pr)
                                        <tr>
                                            <td>#{{ $pr->id }}</td>
                                            <td>{{ $pr->customer?->name }}</td>
                                            <td>{{ $prStatusLabels[$pr->status] ?? $pr->status }}</td>
                                            <td>{{ $pr->final_price_syp ? number_format($pr->final_price_syp).' ل.س' : '—' }}</td>
                                            <td class="text-end">
                                                <form action="{{ route('admin.procurement-batches.add-request', [$batch, $pr]) }}" method="post" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">+ أضيفي</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
