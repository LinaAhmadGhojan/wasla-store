@extends('admin.layout')

@section('content')
<div class="container-fluid py-4" style="max-width: 960px;">
    <h1 class="h3 mb-2">💰 تسعير «على بابك»</h1>
    <p class="text-muted mb-4">الأسعار تظهر فوراً للزبون قبل تأكيد الطلب — بدون «بنتصل فيك».</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('admin.express-pricing.update') }}" method="post">
        @csrf
        @method('put')

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h6 text-muted text-uppercase mb-3">🛒 مشوار محلي (بقالة / صيدلية…)</h2>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">أجور أساسية (ل.س)</label>
                        <input type="number" name="local_base_fee" class="form-control" min="0"
                               value="{{ $p['local_errand']['base_fee_syp'] ?? 20000 }}" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">كل غرض إضافي (ل.س)</label>
                        <input type="number" name="local_per_item" class="form-control" min="0"
                               value="{{ $p['local_errand']['per_item_syp'] ?? 2500 }}" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">معامل مستعجل (×)</label>
                        <input type="number" step="0.01" name="local_urgent_multiplier" class="form-control" min="1" max="3"
                               value="{{ $p['local_errand']['urgent_multiplier'] ?? 1.35 }}" />
                    </div>
                    @foreach(['grocery' => 'بقالة', 'pharmacy' => 'صيدلية', 'produce' => 'خضرة', 'household' => 'منزل', 'other' => 'أخرى'] as $key => $label)
                        <div class="col-md-4">
                            <label class="form-label">أجور {{ $label }} (ل.س)</label>
                            <input type="number" name="cat_{{ $key }}" class="form-control" min="0"
                                   value="{{ $p['local_errand']['category_fees'][$key] ?? 20000 }}" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h6 text-muted text-uppercase mb-3">📦 شحن بين المحافظات</h2>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">نفس المحافظة (ل.س)</label>
                        <input type="number" name="same_governorate_fee" class="form-control" min="0"
                               value="{{ $p['same_governorate_fee_syp'] ?? 25000 }}" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">افتراضي بين محافظات (إذا ما في مسار)</label>
                        <input type="number" name="default_inter_fee" class="form-control" min="0"
                               value="{{ $p['default_inter_governorate_fee_syp'] ?? 95000 }}" />
                    </div>
                </div>
                <h3 class="h6">أحجام الطرد (تُضاف على أجور المسار)</h3>
                <div class="row g-3 mb-3">
                    @foreach(['small' => 'صغير', 'medium' => 'متوسط', 'large' => 'كبير', 'unknown' => 'غير محدد'] as $k => $lbl)
                        <div class="col-md-3">
                            <label class="form-label">{{ $lbl }}</label>
                            <input type="number" name="size_{{ $k }}" class="form-control" min="0"
                                   value="{{ $p['parcel_sizes'][$k] ?? 0 }}" />
                        </div>
                    @endforeach
                </div>
                <h3 class="h6">مسارات محددة (من → إلى)</h3>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>من</th><th>إلى</th><th>الأجور (ل.س)</th><th>الناقل الافتراضي</th></tr></thead>
                        <tbody>
                            @php $routes = $p['governorate_routes'] ?? []; @endphp
                            @for($i = 0; $i < max(4, count($routes)); $i++)
                                @php $r = $routes[$i] ?? []; @endphp
                                <tr>
                                    <td><input name="routes[{{ $i }}][from]" class="form-control form-control-sm" value="{{ $r['from'] ?? '' }}" placeholder="دمشق" /></td>
                                    <td><input name="routes[{{ $i }}][to]" class="form-control form-control-sm" value="{{ $r['to'] ?? '' }}" placeholder="حلب" /></td>
                                    <td><input type="number" name="routes[{{ $i }}][fee_syp]" class="form-control form-control-sm" value="{{ $r['fee_syp'] ?? '' }}" min="0" /></td>
                                    <td>
                                        <select name="routes[{{ $i }}][carrier]" class="form-select form-select-sm">
                                            @foreach($p['carriers'] ?? [] as $c)
                                                <option value="{{ $c['key'] }}" @selected(($r['carrier'] ?? '') === $c['key'])>{{ $c['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h6 text-muted text-uppercase mb-3">🚚 شركات الشحن</h2>
                @foreach($p['carriers'] ?? [] as $c)
                    <div class="border rounded p-3 mb-2 d-flex flex-wrap align-items-center gap-3">
                        <strong>{{ $c['label'] }}</strong>
                        <span class="badge bg-secondary">{{ $c['badge'] ?? '' }}</span>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="carrier_{{ $c['key'] }}_available" value="1"
                                   @checked($c['available'] ?? true) />
                            <label class="form-check-label">متاح</label>
                        </div>
                        <label class="mb-0 ms-auto">رسوم إضافية (ل.س)
                            <input type="number" name="carrier_{{ $c['key'] }}_markup" class="form-control form-control-sm d-inline-block w-auto ms-1"
                                   value="{{ $c['fee_markup_syp'] ?? 0 }}" min="0" />
                        </label>
                    </div>
                @endforeach
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">حفظ التسعير</button>
    </form>
</div>
@endsection
