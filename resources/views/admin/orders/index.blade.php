@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 mb-1">الطلبات المحلية</h1>
                <p class="text-muted mb-0">طلبات المتجر من صفحة الـ Checkout</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- فلاتر --}}
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body py-3">
                <form method="get" class="d-flex gap-2 align-items-center flex-wrap">
                    <label class="form-label mb-0">الحالة:</label>
                    <select name="status" class="form-select w-auto" onchange="this.form.submit()">
                        <option value="">الكل</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <label class="form-label mb-0 me-2">طريقة الدفع:</label>
                    <select name="payment" class="form-select w-auto" onchange="this.form.submit()">
                        <option value="">الكل</option>
                        @foreach($paymentMethods as $key => $label)
                            <option value="{{ $key }}" @selected(request('payment') === $key)>{{ $label }}</option>
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
                            <th>العميل</th>
                            <th>المبلغ (د.إ)</th>
                            <th>≈ ل.س</th>
                            <th>طريقة الدفع</th>
                            <th>حالة الدفع</th>
                            <th>حالة الطلب</th>
                            <th>التاريخ</th>
                            <th class="text-end">إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>#{{ $order->id }}</td>
                                <td>
                                    <div>{{ $order->user?->name ?? '—' }}</div>
                                    <small class="text-muted">{{ $order->user?->phone }}</small>
                                </td>
                                <td dir="ltr">{{ number_format((float)$order->total, 2) }}</td>
                                <td class="text-success">
                                    {{ number_format((float)$order->total * $exchangeRate) }} ل.س
                                </td>
                                <td>
                                    @php $pm = $order->payment?->payment_method; @endphp
                                    <span class="badge bg-secondary">{{ $paymentMethods[$pm] ?? $pm ?? '—' }}</span>
                                </td>
                                <td>
                                    @php $ps = $order->payment?->status; @endphp
                                    @if($ps === 'paid' || $ps === 'confirmed')
                                        <span class="badge bg-success">مدفوع</span>
                                    @elseif($ps === 'pending')
                                        <span class="badge bg-warning text-dark">بانتظار</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $ps ?? '—' }}</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusBg = match($order->status) {
                                            'pending' => 'warning text-dark',
                                            'confirmed' => 'info text-dark',
                                            'preparing' => 'secondary',
                                            'assigned' => 'primary',
                                            'picked_up' => 'primary',
                                            'out_for_delivery', 'shipped' => 'primary',
                                            'delivered' => 'success',
                                            'failed_delivery' => 'danger',
                                            'returned' => 'dark',
                                            'cancelled' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusBg }}">{{ $statuses[$order->status] ?? $order->status }}</span>
                                </td>
                                <td class="text-muted small">{{ $order->placed_at?->format('Y-m-d H:i') ?? $order->created_at->format('Y-m-d') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">عرض</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">لا توجد طلبات.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $orders->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
