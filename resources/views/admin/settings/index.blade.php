@extends('admin.layout')

@section('content')
<div class="container-fluid py-4" style="max-width: 860px;">
    <h1 class="h3 mb-4">⚙️ الإعدادات العامة</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="post">
        @csrf
        @method('put')

        {{-- ── معلومات المتجر ── --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h6 text-muted text-uppercase mb-3">🏪 معلومات المتجر</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">اسم المتجر</label>
                        <input type="text" name="store_name" value="{{ $s['store_name'] ?? 'وصلة' }}" class="form-control" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">رقم واتساب للتواصل</label>
                        <input type="text" name="whatsapp_support" value="{{ $s['whatsapp_support'] ?? '' }}" class="form-control" placeholder="9639XXXXXXXX" dir="ltr" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">العملة الافتراضية للعرض</label>
                        <select name="display_currency" class="form-select">
                            <option value="SYP" @selected(($s['display_currency'] ?? 'SYP') === 'SYP')>ليرة سورية (ل.س)</option>
                            <option value="AED" @selected(($s['display_currency'] ?? '') === 'AED')>درهم إماراتي (د.إ)</option>
                            <option value="USD" @selected(($s['display_currency'] ?? '') === 'USD')>دولار (USD)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">رسوم التوصيل الافتراضية (ل.س)</label>
                        <input type="number" name="default_delivery_fee" value="{{ $s['default_delivery_fee'] ?? 0 }}" class="form-control" min="0" />
                        <div class="form-text">0 = توصيل مجاني</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── طرق الدفع ── --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h6 text-muted text-uppercase mb-1">💳 طرق الدفع المتاحة</h2>
                <p class="text-muted small mb-3">فعّلي أو أوقفي كل طريقة — ستظهر فقط الطرق المفعّلة للعملاء.</p>
                <div class="row g-2">
                    @foreach($allMethods as $key => $label)
                        <div class="col-md-6">
                            <div class="p-3 border rounded-3 d-flex align-items-start gap-3 @if($enabledMethods[$key] ?? true) bg-light @endif">
                                <div class="form-check form-switch mb-0 p-0">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                        name="payment_methods[]"
                                        value="{{ $key }}"
                                        id="pm_{{ $key }}"
                                        @checked($enabledMethods[$key] ?? true)
                                        style="width:2.5em;height:1.4em;margin:0;cursor:pointer;"
                                    />
                                </div>
                                <label for="pm_{{ $key }}" style="cursor:pointer;flex:1;">
                                    <strong>{{ $label }}</strong>
                                    <div class="text-muted small">{{ $instructions[$key] ?? '' }}</div>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── وضع الصيانة ── --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h6 text-muted text-uppercase mb-3">🔧 إعدادات المتجر</h2>
                @php
                $toggles = [
                    ['name'=>'maintenance_mode',   'label'=>'وضع الصيانة',                   'hint'=>'يُخفي المتجر للعملاء مؤقتاً',             'default'=>false],
                    ['name'=>'show_out_of_stock',  'label'=>'إظهار المنتجات المنتهية',        'hint'=>'عرض "نفد المخزون" بدل إخفاء المنتج',      'default'=>true],
                    ['name'=>'allow_guest_cart',   'label'=>'السلة بدون تسجيل دخول',         'hint'=>'السماح للزوار بالتسوق',                    'default'=>true],
                    ['name'=>'show_prices_in_syp', 'label'=>'عرض الأسعار بالليرة السورية',   'hint'=>'يتطلب سعر صرف محدّث',                     'default'=>true],
                ];
                @endphp
                <div class="row g-3">
                    @foreach($toggles as $t)
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 d-flex align-items-start gap-3">
                            <div class="form-check form-switch mb-0 p-0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    name="{{ $t['name'] }}" id="{{ $t['name'] }}" value="1"
                                    @checked($s[$t['name']] ?? $t['default'])
                                    style="width:2.5em;height:1.4em;margin:0;cursor:pointer;" />
                            </div>
                            <label for="{{ $t['name'] }}" style="cursor:pointer;flex:1;">
                                <strong>{{ $t['label'] }}</strong>
                                <div class="text-muted small">{{ $t['hint'] }}</div>
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── أقسام الصفحة الرئيسية ── --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h6 text-muted text-uppercase mb-1">🏠 أقسام الصفحة الرئيسية</h2>
                <p class="text-muted small mb-3">فعّلي أو أوقفي كل قسم وعدّلي العنوان — يظهر فقط المفعّل على الهوم (بما فيها المفضلة).</p>

                <div class="mb-3" style="max-width:200px;">
                    <label class="form-label">عدد المنتجات في كل قسم</label>
                    <input type="number" name="home_section_limit" class="form-control" min="4" max="24" value="{{ $homeSectionLimit }}" />
                </div>

                <div class="row g-3">
                    @foreach($homeSections as $key => $sec)
                        <div class="col-12">
                            <div class="p-3 border rounded-3 @if($sec['enabled'] ?? true) bg-light @endif">
                                <div class="d-flex align-items-start gap-3 mb-2">
                                    <div class="form-check form-switch mb-0 p-0">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            name="home_sections[{{ $key }}][enabled]"
                                            value="1"
                                            id="hs_{{ $key }}"
                                            @checked($sec['enabled'] ?? true)
                                            style="width:2.5em;height:1.4em;margin:0;cursor:pointer;"
                                        />
                                    </div>
                                    <label for="hs_{{ $key }}" style="cursor:pointer;flex:1;">
                                        <strong>{{ $sec['title'] }}</strong>
                                        <div class="text-muted small font-monospace">{{ $key }}</div>
                                    </label>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label small mb-1">شارة صغيرة</label>
                                        <input type="text" name="home_sections[{{ $key }}][eyebrow]" class="form-control form-control-sm" value="{{ $sec['eyebrow'] }}" />
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label small mb-1">عنوان القسم</label>
                                        <input type="text" name="home_sections[{{ $key }}][title]" class="form-control form-control-sm" value="{{ $sec['title'] }}" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── إرجاع واستبدال ── --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h6 text-muted text-uppercase mb-1">↩️ إرجاع واستبدال</h2>
                <p class="text-muted small mb-3">المتعارف: المدة من <strong>الاستلام</strong> (مو من الطلب). يمكن تعطيل منتج معيّن من صفحة المنتج.</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">أيام الإرجاع الافتراضية</label>
                        <input type="number" name="return_days_default" class="form-control" min="1" max="90" value="{{ $s['return_days_default'] ?? 7 }}" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">أيام الاستبدال الافتراضية</label>
                        <input type="number" name="exchange_days_default" class="form-control" min="1" max="90" value="{{ $s['exchange_days_default'] ?? 7 }}" />
                    </div>
                </div>
            </div>
        </div>

        {{-- ── مكافأة التقييمات ── --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h6 text-muted text-uppercase mb-1">⭐ مكافأة تقييم المنتج</h2>
                <p class="text-muted small mb-3">بعد استلام الطلبية يمكن للعميل التقييم — تُضاف المكافأة لرصيد المتجر (ل.س).</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">مكافأة تعليق نصي (ل.س)</label>
                        <input type="number" name="review_reward_text_syp" class="form-control" min="0" value="{{ $reviewRewards['text_syp'] ?? 500 }}" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">مكافأة مع صورة (ل.س)</label>
                        <input type="number" name="review_reward_photo_syp" class="form-control" min="0" value="{{ $reviewRewards['with_photo_syp'] ?? 1000 }}" />
                        <div class="form-text">إذا رُفعت صورة تُعطى هذه القيمة بدل النص فقط.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── أرقام حسابات الدفع ── --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h6 text-muted text-uppercase mb-3">📞 أرقام وحسابات الدفع</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">رقم سيرياتيل كاش</label>
                        <input type="text" name="syriatel_number" value="{{ $s['syriatel_number'] ?? '' }}" class="form-control" dir="ltr" placeholder="0941XXXXXXX" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">رقم MTN كاش</label>
                        <input type="text" name="mtn_number" value="{{ $s['mtn_number'] ?? '' }}" class="form-control" dir="ltr" placeholder="0961XXXXXXX" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">رقم شام كاش</label>
                        <input type="text" name="sham_number" value="{{ $s['sham_number'] ?? '' }}" class="form-control" dir="ltr" placeholder="011XXXXXXX" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">IBAN البنك</label>
                        <input type="text" name="bank_iban" value="{{ $s['bank_iban'] ?? '' }}" class="form-control" dir="ltr" placeholder="SY00 0000 0000..." />
                    </div>
                    <div class="col-12">
                        <label class="form-label">اسم صاحب الحساب البنكي</label>
                        <input type="text" name="bank_holder" value="{{ $s['bank_holder'] ?? '' }}" class="form-control" />
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary px-4">💾 حفظ الإعدادات</button>
    </form>
</div>
@endsection
