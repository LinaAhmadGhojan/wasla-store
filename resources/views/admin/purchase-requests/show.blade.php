@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 mb-1">طلب #{{ $purchaseRequest->id }}</h1>
                <p class="text-muted mb-0">سعر الصرف الحالي: 1 د.إ = {{ number_format($exchangeRate) }} ل.س</p>
            </div>
            <a href="{{ route('admin.purchase-requests.index') }}" class="btn btn-outline-secondary">رجوع</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h2 class="h6 text-uppercase text-muted mb-3">التفاصيل</h2>
                        <p class="mb-1"><strong>Order:</strong> #{{ $purchaseRequest->id }} · {{ $purchaseRequest->customer?->name }}</p>
                        <p class="mb-1"><strong>الإيميل:</strong> {{ $purchaseRequest->customer?->email }}</p>
                        <p class="mb-1"><strong>المنصة:</strong> {{ $purchaseRequest->platform?->name ?? '—' }}</p>
                        <p class="mb-1"><strong>الرابط:</strong>
                            @if($purchaseRequest->url)
                                <a href="{{ $purchaseRequest->url }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($purchaseRequest->url, 40) }}</a>
                            @else — @endif
                        </p>
                        <p class="mb-1"><strong>الحالة:</strong>
                            <span class="badge bg-info text-dark">{{ $purchaseRequest->statusLabel() }}</span>
                        </p>
                        @if($purchaseRequest->quoted_at)
                            <p class="mb-1"><strong>تاريخ العرض:</strong> {{ $purchaseRequest->quoted_at->format('Y-m-d H:i') }}</p>
                            <p class="mb-1"><strong>سعر الصرف وقت العرض:</strong> <span dir="ltr">{{ number_format((float) $purchaseRequest->exchange_rate) }} ل.س</span></p>
                        @endif
                        <p class="mb-0"><strong>ملاحظات Order:</strong><br>{{ $purchaseRequest->customer_notes ?: '—' }}</p>
                    </div>
                </div>

                @if($purchaseRequest->final_price_syp)
                    <div class="card shadow-sm border-0 border-success border-opacity-50 mb-4">
                        <div class="card-body text-center">
                            <span class="text-muted small">عرض Order (ل.س)</span>
                            <p class="display-6 mb-0">{{ $currency->formatSyp($purchaseRequest->final_price_syp) }}</p>
                            <p class="text-muted small mb-0" dir="ltr">{{ $currency->formatAed((float) $purchaseRequest->final_price) }} تكلفة</p>
                        </div>
                    </div>
                @endif

                @if($purchaseRequest->procurementBatch)
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <h2 class="h6 text-muted text-uppercase mb-2">دفعة الشراء</h2>
                            <a href="{{ route('admin.procurement-batches.show', $purchaseRequest->procurementBatch) }}">
                                {{ $purchaseRequest->procurementBatch->reference }}
                            </a>
                            · {{ $purchaseRequest->procurementBatch->statusLabel() }}
                        </div>
                    </div>
                @endif

                @php $payment = $purchaseRequest->latestPayment; @endphp
                @if($payment)
                    <div class="card shadow-sm border-0 border-warning border-opacity-50 mb-4">
                        <div class="card-body">
                            <h2 class="h6 text-muted text-uppercase mb-3">الدفع</h2>
                            <p class="mb-1"><strong>المبلغ:</strong> {{ number_format($payment->amount_syp) }} ل.س</p>
                            <p class="mb-1"><strong>الطريقة:</strong>
                                <span class="badge bg-secondary">{{ $payment->methodLabel() }}</span>
                            </p>
                            <p class="mb-1"><strong>الحالة:</strong>
                                @if($payment->status === 'confirmed')
                                    <span class="badge bg-success">✓ مؤكّد</span>
                                @elseif($payment->status === 'rejected')
                                    <span class="badge bg-danger">مرفوض</span>
                                @else
                                    <span class="badge bg-warning text-dark">بانتظار التأكيد</span>
                                @endif
                            </p>
                            @if($payment->customer_notes)
                                <p class="mb-1"><strong>ملاحظة Order:</strong> {{ $payment->customer_notes }}</p>
                            @endif
                            @if($payment->submitted_at)
                                <p class="mb-1 small text-muted">أُرسل: {{ $payment->submitted_at->format('Y-m-d H:i') }}</p>
                            @endif
                            @if($payment->confirmed_at)
                                <p class="mb-1 small text-muted">تأكّد: {{ $payment->confirmed_at->format('Y-m-d H:i') }}</p>
                            @endif
                            @if($payment->receipt_path)
                                <p class="mb-2">
                                    <a href="{{ asset('storage/'.$payment->receipt_path) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
                                        🖼 عرض الإيصال
                                    </a>
                                </p>
                            @endif
                            @if($payment->status === 'submitted')
                                <div class="d-flex gap-2 mt-2">
                                    <form action="{{ route('admin.purchase-requests.confirm-payment', $purchaseRequest) }}" method="post" class="flex-fill">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm w-100">✓ تأكيد الدفع</button>
                                    </form>
                                    <form action="{{ route('admin.purchase-requests.reject-payment', $purchaseRequest) }}" method="post" class="flex-fill">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('رفض الدفع؟')">✗ رفض</button>
                                    </form>
                                </div>
                                @if($payment->method === 'cash_on_delivery')
                                    <p class="small text-muted mt-1 mb-0">💡 كاش عند التسليم — أكّدي بعد استلام المبلغ يدوياً.</p>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Store credit top-up (admin) --}}
                @if($purchaseRequest->customer)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h2 class="h6 text-muted text-uppercase mb-2">رصيد وصلة — Order</h2>
                        <p class="mb-2">الرصيد الحالي: <strong>{{ number_format($purchaseRequest->customer->store_credit_syp) }} ل.س</strong></p>
                        <form action="{{ route('admin.customers.add-credit', $purchaseRequest->customer) }}" method="post" class="d-flex gap-2">
                            @csrf
                            <input type="number" name="amount_syp" placeholder="مبلغ ل.س" min="1" class="form-control form-control-sm" required />
                            <input type="text" name="notes" placeholder="ملاحظة" class="form-control form-control-sm" />
                            <button type="submit" class="btn btn-outline-primary btn-sm text-nowrap">+ شحن رصيد</button>
                        </form>
                    </div>
                </div>
                @endif

                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="h6 text-uppercase text-muted mb-3">تغيير الحالة</h2>
                        <form action="{{ route('admin.purchase-requests.status', $purchaseRequest) }}" method="post" class="d-flex gap-2">
                            @csrf
                            @method('patch')
                            <select name="status" class="form-select">
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" @selected($purchaseRequest->status === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary text-nowrap">تحديث</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="h6 text-uppercase text-muted mb-3">عرض السعر (أنتِ بالدرهم · Order بالليرة)</h2>

                        <form action="{{ route('admin.purchase-requests.quote', $purchaseRequest) }}" method="post" id="quoteForm">
                            @csrf
                            @method('put')

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">تقدير (د.إ)</label>
                                    <input type="number" step="0.01" name="estimated_price" id="estimated_price"
                                        value="{{ old('estimated_price', $purchaseRequest->estimated_price) }}" class="form-control aed-input" />
                                    <div class="form-text syp-hint" data-for="estimated_price"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">السعر النهائي (د.إ) *</label>
                                    <input type="number" step="0.01" name="final_price" id="final_price"
                                        value="{{ old('final_price', $purchaseRequest->final_price) }}" class="form-control aed-input" />
                                    <div class="form-text syp-hint fw-semibold text-success" data-for="final_price"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">ملاحظات الأدمن</label>
                                    <input type="text" name="admin_notes" value="{{ old('admin_notes', $purchaseRequest->admin_notes) }}" class="form-control" />
                                </div>
                            </div>

                            <div class="table-responsive mb-3">
                                <table class="table table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>المنتج</th>
                                            <th>المقاس/اللون</th>
                                            <th>كمية</th>
                                            <th>مصدر (د.إ)</th>
                                            <th>خدمة</th>
                                            <th>شحن</th>
                                            <th>نهائي (د.إ)</th>
                                            <th>≈ ل.س</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($purchaseRequest->items as $index => $item)
                                            <tr>
                                                <td>
                                                    {{ $item->product_name }}
                                                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}" />
                                                </td>
                                                <td>
                                                    @php
                                                        $vd = is_array($item->variant_data) ? $item->variant_data : [];
                                                        $colorLabel = $vd['color_name'] ?? $vd['color'] ?? null;
                                                        $sizeLabel = $vd['size_name'] ?? $vd['size'] ?? null;
                                                        $skuLabel = $vd['sku'] ?? null;
                                                        $image = $vd['image'] ?? null;
                                                    @endphp
                                                    @if($image)
                                                        <img src="{{ $image }}" alt="" width="36" height="36" style="object-fit:cover;border-radius:6px;margin-inline-end:6px;vertical-align:middle" />
                                                    @endif
                                                    @if($colorLabel || $sizeLabel || $skuLabel)
                                                        <div>
                                                            @if($colorLabel)<strong>{{ $colorLabel }}</strong>@endif
                                                            @if($sizeLabel) · {{ $sizeLabel }}@endif
                                                        </div>
                                                        @if($skuLabel)<small class="text-muted">SKU: {{ $skuLabel }}</small>@endif
                                                    @elseif($item->variant_data)
                                                        <small class="text-muted">{{ collect($item->variant_data)->filter(fn($v) => is_scalar($v))->map(fn($v, $k) => "$k: $v")->implode(', ') }}</small>
                                                    @else — @endif
                                                </td>
                                                <td>{{ $item->quantity }}</td>
                                                <td><input type="number" step="0.01" name="items[{{ $index }}][source_price]" value="{{ $item->source_price }}" class="form-control form-control-sm" /></td>
                                                <td><input type="number" step="0.01" name="items[{{ $index }}][service_fee]" value="{{ $item->service_fee }}" class="form-control form-control-sm" /></td>
                                                <td><input type="number" step="0.01" name="items[{{ $index }}][shipping_fee]" value="{{ $item->shipping_fee }}" class="form-control form-control-sm" /></td>
                                                <td><input type="number" step="0.01" name="items[{{ $index }}][final_price]" value="{{ $item->final_price }}" class="form-control form-control-sm aed-item-input" data-syp-cell="item-syp-{{ $index }}" /></td>
                                                <td class="small text-success" id="item-syp-{{ $index }}">
                                                    @if($item->final_price_syp){{ $currency->formatSyp($item->final_price_syp) }}@else—@endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <button type="submit" class="btn btn-primary">حفظ العرض — Order (ل.س)</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    const RATE = @json($exchangeRate);

    function toSyp(aed) {
        const n = Number(aed);
        if (!n || n <= 0) return '';
        return '≈ ' + Math.round(n * RATE).toLocaleString('en-US') + ' ل.س';
    }

    function refreshHints() {
        document.querySelectorAll('.aed-input').forEach((input) => {
            const hint = document.querySelector('.syp-hint[data-for="' + input.id + '"]');
            if (hint) hint.textContent = toSyp(input.value);
        });
        document.querySelectorAll('.aed-item-input').forEach((input) => {
            const cell = document.getElementById(input.dataset.sypCell);
            if (cell) cell.textContent = toSyp(input.value) || '—';
        });
    }

    document.querySelectorAll('.aed-input, .aed-item-input').forEach((el) => {
        el.addEventListener('input', refreshHints);
    });
    refreshHints();
    </script>
@endsection
