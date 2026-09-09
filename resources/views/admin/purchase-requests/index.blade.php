@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 mb-1">طلبات الشراء</h1>
                <p class="text-muted mb-0">طلبات SHEIN والخارج — أنتِ بالدرهم، Order بالليرة (1 د.إ = {{ number_format($exchangeRate) }} ل.س)</p>
            </div>
            <a href="{{ route('admin.exchange-rate.edit') }}" class="btn btn-outline-primary btn-sm">تعديل سعر الصرف</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body py-3">
                <form method="get" class="d-flex gap-2 align-items-center flex-wrap">
                    <label class="form-label mb-0">فلترة:</label>
                    <select name="status" class="form-select w-auto" onchange="this.form.submit()">
                        <option value="">الكل</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected($currentStatus === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Order</th>
                            <th>المنصة</th>
                            <th>الحالة</th>
                            <th>تقدير (د.إ)</th>
                            <th>عرض Order (ل.س)</th>
                            <th>التاريخ</th>
                            <th class="text-end">إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseRequests as $pr)
                            <tr>
                                <td>#{{ $pr->id }}</td>
                                <td>#{{ $pr->id }} · {{ $pr->customer?->name ?? '—' }}</td>
                                <td>{{ $pr->platform?->name ?? '—' }}</td>
                                <td><span class="badge bg-info text-dark">{{ $pr->statusLabel() }}</span></td>
                                <td dir="ltr">{{ $pr->final_price !== null ? $currency->formatAed((float) $pr->final_price) : ($pr->estimated_price !== null ? $currency->formatAed((float) $pr->estimated_price) : '—') }}</td>
                                <td>
                                    @if($pr->final_price_syp)
                                        <strong>{{ $currency->formatSyp($pr->final_price_syp) }}</strong>
                                        @if($pr->exchange_rate)
                                            <span class="text-muted small d-block" dir="ltr">@ {{ number_format($pr->exchange_rate) }}</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $pr->created_at->format('Y-m-d') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.purchase-requests.show', $pr) }}" class="btn btn-sm btn-outline-primary">مراجعة</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">لا توجد طلبات.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $purchaseRequests->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
