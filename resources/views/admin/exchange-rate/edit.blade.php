@extends('admin.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">سعر الصرف</h1>
            <p class="text-muted mb-0">1 درهم إماراتي = كم ليرة سورية — Order يشوف الليرة فقط.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">لوحة التحكم</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 border-primary border-opacity-25">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase mb-3">السعر الحالي</h2>
                    <p class="display-6 mb-1" dir="ltr">1 د.إ = {{ number_format($currentRate) }} ل.س</p>
                    @if($currentRecord)
                        <p class="text-muted small mb-4">
                            آخر تحديث: {{ $currentRecord->created_at->format('Y-m-d H:i') }}
                            @if($currentRecord->notes) · {{ $currentRecord->notes }} @endif
                        </p>
                    @endif

                    <form action="{{ route('admin.exchange-rate.update') }}" method="post">
                        @csrf
                        @method('put')
                        <div class="mb-3">
                            <label class="form-label">سعر الصرف (1 د.إ = … ل.س) *</label>
                            <input type="text" name="rate" inputmode="decimal" class="form-control form-control-lg money-commas"
                                value="{{ old('rate', number_format($currentRate, 0, '.', ',')) }}" required dir="ltr">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label">ضريبة المنتج (ل.س)</label>
                                <input type="text" name="product_fee_aed" inputmode="decimal" class="form-control money-commas"
                                    value="{{ old('product_fee_aed', number_format($productFee, 0, '.', ',')) }}" required dir="ltr">
                                <div class="form-text">بتتنضاف بعد ضرب الدرهم بسعر الصرف</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">ضريبة الإكسسوار (ل.س)</label>
                                <input type="text" name="accessory_fee_aed" inputmode="decimal" class="form-control money-commas"
                                    value="{{ old('accessory_fee_aed', number_format($accessoryFee, 0, '.', ',')) }}" required dir="ltr">
                                <div class="form-text">نفس القاعدة للإكسسوارات</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ملاحظة (اختياري)</label>
                            <input type="text" name="notes" class="form-control" value="{{ old('notes') }}"
                                placeholder="مثلاً: سعر اليوم 18/08">
                        </div>
                        <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
                    </form>

                    <div class="alert alert-light border mt-4 mb-0 small">
                        <strong>قاعدة وصلة:</strong>
                        سعر الزبون ل.س = (سعر الدرهم × سعر الصرف) + الضريبة<br>
                        منتج: درهم × {{ number_format($currentRate) }} + {{ number_format($productFee, 0) }}<br>
                        إكسسوار: درهم × {{ number_format($currentRate) }} + {{ number_format($accessoryFee, 0) }}<br>
                        مثال 20 د.إ منتج → {{ number_format(20 * $currentRate + $productFee) }} ل.س
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase mb-3">سجل الأسعار</h2>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>السعر</th>
                                    <th>منتج / إكسسوار</th>
                                    <th>ملاحظة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($history as $row)
                                    <tr class="{{ $row->is_current ? 'table-success' : '' }}">
                                        <td>{{ $row->created_at->format('Y-m-d H:i') }}</td>
                                        <td dir="ltr">{{ number_format($row->rate) }} ل.س</td>
                                        <td class="small">+{{ number_format($row->product_fee_aed ?? 75, 0) }} / +{{ number_format($row->accessory_fee_aed ?? 50, 0) }} ل.س</td>
                                        <td>{{ $row->notes ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">لا يوجد سجل بعد.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.money-commas').forEach((input) => {
    const parse = (v) => String(v || '').replace(/[,،\s]/g, '');
    const format = (v) => {
        const n = Number(parse(v));
        return Number.isFinite(n) ? n.toLocaleString('en-US') : v;
    };
    input.addEventListener('blur', () => { input.value = format(input.value); });
    input.form?.addEventListener('submit', () => { input.value = parse(input.value); });
});
</script>
@endsection
