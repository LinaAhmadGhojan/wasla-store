@extends('admin.layout')

@section('content')
@php
    $order = $order; // ensure in scope
    $otp = $order->getRawOriginal('delivery_otp') ?? $order->delivery_otp;
@endphp
<style>
    .order-show { max-width: 1180px; }
    .order-hero {
        background: #fff; border-radius: 1.1rem; padding: 1.1rem 1.25rem;
        border: 1px solid rgba(28,114,130,.1); margin-bottom: 1rem;
        display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between;
    }
    .order-hero h1 { font-size: 1.35rem; margin: 0; color: #0b3d44; }
    .order-hero .meta { color: #4d6b72; font-size: .9rem; }
    .chip {
        display: inline-flex; align-items: center; gap: .35rem;
        background: #e8f6f8; color: #0b3d44; border-radius: 999px;
        padding: .35rem .8rem; font-weight: 700; font-size: .9rem;
    }
    .panel {
        background: #fff; border: 1px solid rgba(28,114,130,.1);
        border-radius: 1.1rem; padding: 1.15rem 1.25rem; height: 100%;
    }
    .panel h2 {
        font-size: .78rem; letter-spacing: .06em; text-transform: uppercase;
        color: #6c7d84; margin: 0 0 .85rem; font-weight: 800;
    }
    .kv { margin: 0; font-size: .92rem; }
    .kv dt { color: #6c7d84; font-weight: 600; margin-top: .35rem; }
    .kv dd { margin: 0; color: #132f37; }
    .steps-row {
        list-style: none; padding: 0; margin: 0 0 1rem;
        display: grid; grid-template-columns: repeat(5, 1fr); gap: .4rem; text-align: center;
    }
    .steps-row li { opacity: .4; }
    .steps-row li.done, .steps-row li.current { opacity: 1; }
    .steps-row .dot {
        width: 18px; height: 18px; border-radius: 50%; margin: 0 auto .35rem;
        background: #c5d8dc; color: #fff; font-size: .7rem; font-weight: 800;
        display: flex; align-items: center; justify-content: center;
    }
    .steps-row li.done .dot, .steps-row li.current .dot { background: #1c7282; }
    .steps-row .lbl { font-size: .72rem; font-weight: 700; color: #0b3d44; line-height: 1.25; }
    .check-list { list-style: none; padding: 0; margin: 0; }
    .check-list li {
        display: flex; align-items: center; gap: .75rem;
        padding: .65rem .2rem; border-bottom: 1px solid rgba(28,114,130,.07);
    }
    .check-list li:last-child { border-bottom: 0; }
    .check-box {
        width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border: 2px solid #c5d8dc; color: transparent; font-weight: 900;
        background: #fff;
    }
    .check-list li.done .check-box {
        background: #1c7282; border-color: #1c7282; color: #fff;
    }
    .check-list li.current .check-box {
        border-color: #1c7282; box-shadow: 0 0 0 3px rgba(28,114,130,.15);
    }
    .check-list .label { font-weight: 700; color: #132f37; }
    .check-list .when { font-size: .78rem; color: #6c7d84; }
    .check-list .actions { margin-inline-start: auto; }
    .soft-actions .btn { margin-bottom: .4rem; }
    .timeline-mini { max-height: 280px; overflow: auto; }
    .timeline-mini .ev {
        border-inline-start: 3px solid #1c7282; padding: .35rem .75rem; margin-bottom: .65rem;
    }
    .timeline-mini .ev.logistics { border-color: #f0a202; }
    details.more > summary {
        cursor: pointer; font-weight: 700; color: #1c7282; list-style: none;
    }
    details.more > summary::-webkit-details-marker { display: none; }
    @media (max-width: 768px) {
        .steps-row { grid-template-columns: repeat(3, 1fr); }
    }
</style>

<div class="container-fluid py-3 order-show">
    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 small">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="order-hero">
        <div>
            <h1>طلب #{{ $order->id }}
                <span class="chip">{{ $customerStatusLabel }}</span>
            </h1>
            <div class="meta mt-1">
                {{ $order->sourcesLabel() }}
                @if($order->tracking_number) · تتبع <code>{{ $order->tracking_number }}</code>@endif
                · {{ optional($order->placed_at)->format('Y-m-d H:i') }}
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-outline-primary btn-sm" href="{{ url('/orders/'.$order->id.'/track') }}" target="_blank">تتبع الزبون</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.orders.index') }}">رجوع</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="panel">
                <h2>العميل والتوصيل</h2>
                <dl class="kv">
                    <dt>الاسم</dt><dd>{{ $order->user?->name ?? '—' }}</dd>
                    <dt>الهاتف</dt><dd>{{ $order->user?->phone ?? '—' }}</dd>
                    @if($order->shippingAddress)
                        <dt>العنوان</dt>
                        <dd>
                            {{ $order->shippingAddress->label ?: $order->shippingAddress->label_type_ar }} —
                            {{ $order->shippingAddress->recipient_name }} · {{ $order->shippingAddress->phone }}<br>
                            {{ $order->shippingAddress->street_address }}<br>
                            {{ collect([$order->shippingAddress->state, $order->shippingAddress->city, $order->shippingAddress->country])->filter()->implode(' · ') }}
                            @if($order->shippingAddress->courier_notes)
                                <br><strong>ملاحظات المندوب:</strong> {{ $order->shippingAddress->courier_notes }}
                            @endif
                            @if($order->shippingAddress->latitude && $order->shippingAddress->longitude)
                                <br>
                                <a href="https://www.google.com/maps?q={{ $order->shippingAddress->latitude }},{{ $order->shippingAddress->longitude }}" target="_blank" rel="noopener">
                                    فتح الموقع على الخريطة
                                </a>
                            @endif
                        </dd>
                    @endif
                </dl>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel">
                <h2>الدفع</h2>
                <dl class="kv">
                    <dt>الطريقة</dt>
                    <dd>{{ $paymentMethods[$order->payment?->payment_method] ?? $order->payment?->payment_method ?? '—' }}</dd>
                    <dt>الحالة</dt>
                    <dd>
                        @if(($order->payment?->status) === 'paid')
                            <span class="badge bg-success">مدفوع</span>
                        @else
                            <span class="badge bg-warning text-dark">بانتظار تأكيد الأدمن</span>
                        @endif
                    </dd>
                    @if($order->payment?->transaction_id || data_get($order->payment?->metadata, 'transfer_code'))
                        <dt>كود التحويل / العملية</dt>
                        <dd><code dir="ltr">{{ $order->payment->transaction_id ?: data_get($order->payment->metadata, 'transfer_code') }}</code></dd>
                    @endif
                    <dt>المبلغ</dt>
                    <dd dir="ltr">{{ number_format((float)$order->total, 2) }} د.إ
                        <span class="text-success">≈ {{ number_format((float)$order->total * $exchangeRate) }} ل.س</span>
                    </dd>
                </dl>
                @if($order->payment?->status === 'pending' && !in_array($order->payment?->payment_method, ['cash_on_delivery'], true))
                    <form action="{{ route('admin.orders.confirm-payment', $order) }}" method="post" class="mt-2">
                        @csrf
                        <button class="btn btn-success btn-sm w-100">✓ تأكيد الدفع وبدء التجهيز</button>
                    </form>
                @endif
                @if($order->payment?->payment_method === 'cash_on_delivery')
                    <div class="alert alert-warning py-2 small mt-2 mb-0">كاش عند الاستلام — الدفع كامل عند التسليم.</div>
                @endif
                @if($order->payment?->receipt_path)
                    <a href="{{ asset('storage/'.$order->payment->receipt_path) }}" target="_blank" class="btn btn-outline-secondary btn-sm w-100 mt-2">
                        🖼 عرض إيصال الزبون
                    </a>
                    <div class="small text-muted mt-1">
                        رُفع {{ optional($order->payment->receipt_uploaded_at)->format('Y-m-d H:i') ?: '—' }}
                    </div>
                @endif
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel">
                <h2>تحديث حالة الزبون</h2>
                <ol class="steps-row">
                    @foreach($customerStatusSteps as $step)
                        <li class="{{ $step['done'] ? 'done' : '' }} {{ $step['current'] ? 'current' : '' }}">
                            <div class="dot">{{ $step['done'] ? '✓' : '' }}</div>
                            <div class="lbl">{{ $step['label'] }}</div>
                        </li>
                    @endforeach
                </ol>
                <form action="{{ route('admin.orders.update-status', $order) }}" method="post" class="vstack gap-2">
                    @csrf
                    @method('patch')
                    <select name="status" class="form-select form-select-sm" id="statusSelect" required>
                        <option value="">الحالة التالية…</option>
                        @foreach($nextStatuses as $key)
                            <option value="{{ $key }}">{{ $statuses[$key] ?? $key }}</option>
                        @endforeach
                    </select>
                    <div id="failureBox" class="d-none">
                        <select name="failure_reason" class="form-select form-select-sm">
                            <option value="">سبب الفشل</option>
                            @foreach($failureReasons as $fkey => $flabel)
                                <option value="{{ $fkey }}">{{ $flabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="text" name="note" class="form-control form-control-sm" placeholder="ملاحظة (اختياري)">
                    <button class="btn btn-primary btn-sm" @disabled(empty($nextStatuses))>تحديث</button>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="panel">
                <h2>مسار اللوجستي (يظهر للزبون) — بالترتيب</h2>
                <ul class="check-list">
                    @foreach($logisticsChecklist as $step)
                        <li class="{{ $step['done'] ? 'done' : '' }} {{ $step['current'] ? 'current' : '' }}">
                            <span class="check-box">✓</span>
                            <div>
                                <div class="label">{{ $step['label'] }}</div>
                                @if($step['done'] && $step['at'])
                                    <div class="when">{{ \Illuminate\Support\Carbon::parse($step['at'])->format('Y-m-d H:i') }}</div>
                                @elseif($step['current'])
                                    <div class="when">الخطوة الحالية — اضغطي شيك</div>
                                @endif
                            </div>
                            <div class="actions">
                                @if($step['done'])
                                    <span class="badge bg-success">تم</span>
                                @else
                                    <form action="{{ route('admin.orders.logistics', $order) }}" method="post">
                                        @csrf
                                        <input type="hidden" name="logistics" value="{{ $step['key'] }}">
                                        <button class="btn btn-sm {{ $step['current'] ? 'btn-primary' : 'btn-outline-primary' }}">✓ تم</button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                <p class="small text-muted mb-0 mt-2">المراحل مرتّبة للعرض. علّمي ✓ على اللي صار (مثلاً تقدري تتخطي «الشحن بالخارج» لطلب محلي). الزبون يشوف نفس الشيكليست.</p>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="panel soft-actions">
                <h2>التسليم / المندوب</h2>
                <p class="mb-1 small"><strong>المندوب:</strong> {{ $order->driver?->name ?? '—' }}</p>
                <p class="mb-2 small"><strong>OTP:</strong> <code class="fs-6">{{ $otp ?: '—' }}</code>
                    @if($order->delivery_otp_verified_at)<span class="badge bg-success">تم التحقق</span>@endif
                </p>

                <form action="{{ route('admin.orders.assign-driver', $order) }}" method="post" class="d-flex gap-2 mb-2">
                    @csrf
                    <select name="driver_id" class="form-select form-select-sm" required>
                        <option value="">اختر سائق</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" @selected($order->driver_id === $driver->id)>{{ $driver->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-outline-primary text-nowrap">إسناد</button>
                </form>

                <details class="more mt-2">
                    <summary>المزيد: OTP · تسليم · فشل</summary>
                    <div class="pt-2 vstack gap-2">
                        <form action="{{ route('admin.orders.regenerate-otp', $order) }}" method="post">
                            @csrf
                            <button class="btn btn-outline-secondary btn-sm w-100">إعادة توليد OTP</button>
                        </form>
                        <form action="{{ route('admin.orders.verify-otp', $order) }}" method="post" class="d-flex gap-2">
                            @csrf
                            <input name="otp" class="form-control form-control-sm" placeholder="تحقق OTP" required>
                            <button class="btn btn-outline-success btn-sm">تحقق</button>
                        </form>
                        <form action="{{ route('admin.orders.deliver', $order) }}" method="post" enctype="multipart/form-data" class="vstack gap-2">
                            @csrf
                            <input name="otp" class="form-control form-control-sm" placeholder="OTP عند التسليم">
                            <input type="file" name="signature" class="form-control form-control-sm" accept="image/*">
                            <input type="file" name="proof_photo" class="form-control form-control-sm" accept="image/*">
                            <button class="btn btn-success btn-sm">✓ تسليم + إثبات</button>
                        </form>
                        <form action="{{ route('admin.orders.fail-delivery', $order) }}" method="post" class="vstack gap-2">
                            @csrf
                            <select name="failure_reason" class="form-select form-select-sm" required>
                                <option value="">سبب الفشل</option>
                                @foreach($failureReasons as $fkey => $flabel)
                                    <option value="{{ $fkey }}">{{ $flabel }}</option>
                                @endforeach
                            </select>
                            <input name="note" class="form-control form-control-sm" placeholder="ملاحظة">
                            <button class="btn btn-outline-danger btn-sm">تسجيل فشل</button>
                        </form>
                        @if($order->signature_path || $order->proof_photo_path)
                            <div class="d-flex gap-2 flex-wrap">
                                @if($order->signature_path)
                                    <a href="{{ asset('storage/'.$order->signature_path) }}" target="_blank" class="btn btn-sm btn-light">التوقيع</a>
                                @endif
                                @if($order->proof_photo_path)
                                    <a href="{{ asset('storage/'.$order->proof_photo_path) }}" target="_blank" class="btn btn-sm btn-light">الإثبات</a>
                                @endif
                            </div>
                        @endif
                    </div>
                </details>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="panel">
                <h2>الأصناف · {{ $order->sourcesLabel() }}</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>المصدر</th>
                                <th>المنتج</th>
                                <th>الكمية</th>
                                <th>الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        @if(($item->source_type ?? 'local') === 'external')
                                            <span class="badge text-bg-dark">{{ $item->sourceLabel() }}</span>
                                        @else
                                            <span class="badge text-bg-success">وصلة</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $item->displayName() }}
                                        @if($item->external_url)
                                            <div><a href="{{ $item->external_url }}" target="_blank" class="small">رابط</a></div>
                                        @endif
                                    </td>
                                    <td>{{ $item->quantity }}</td>
                                    <td dir="ltr"><strong>{{ number_format((float)$item->line_total, 2) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end fw-bold">الإجمالي</td>
                                <td dir="ltr"><strong>{{ number_format((float)$order->total, 2) }} د.إ</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @if($order->purchaseRequests?->isNotEmpty())
                    <hr>
                    <div class="small">
                        شراء خارجي:
                        @foreach($order->purchaseRequests as $pr)
                            <a href="{{ route('admin.purchase-requests.show', $pr) }}">#{{ $pr->id }} {{ $pr->platform?->name }}</a>@if(!$loop->last) · @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel">
                <h2>سجل الأحداث</h2>
                <div class="timeline-mini">
                    @forelse($order->deliveryEvents->sortByDesc('id') as $event)
                        @php $isLog = !empty($event->meta['logistics']); @endphp
                        <div class="ev {{ $isLog ? 'logistics' : '' }}">
                            <div class="fw-bold small">{{ $event->title }}</div>
                            <div class="text-muted" style="font-size:.75rem">{{ $event->created_at?->format('m-d H:i') }} · {{ $event->actor?->name ?? 'النظام' }}</div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">لا أحداث.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const statusSelect = document.getElementById('statusSelect');
    const failureBox = document.getElementById('failureBox');
    statusSelect?.addEventListener('change', () => {
        failureBox.classList.toggle('d-none', statusSelect.value !== 'failed_delivery');
    });
</script>
@endsection
