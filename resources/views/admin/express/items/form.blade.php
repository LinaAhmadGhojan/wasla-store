@extends('admin.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $item->exists ? 'تعديل طبق' : 'طبق جديد' }}</h1>
            <p class="text-muted mb-0">مثل الموضة: وصف + حصص (نصف/كيلو) + إضافات (حار/ثوم…)</p>
        </div>
        <a href="{{ route('admin.express-items.index') }}" class="btn btn-outline-secondary">رجوع</a>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="post" action="{{ $item->exists ? route('admin.express-items.update', $item) : route('admin.express-items.store') }}">
                @csrf
                @if($item->exists) @method('put') @endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">المتجر</label>
                        <select name="express_store_id" class="form-select" required>
                            @foreach($stores as $s)
                                <option value="{{ $s->id }}" @selected(old('express_store_id', $item->express_store_id) == $s->id)>{{ $s->store_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">التصنيف</label>
                        <select name="express_category_id" class="form-select">
                            <option value="">—</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('express_category_id', $item->express_category_id) == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الاسم</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $item->name) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الوحدة الافتراضية</label>
                        <input type="text" name="unit_label" class="form-control" value="{{ old('unit_label', $item->unit_label) }}" placeholder="حصة / كيلو / نصف">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', $item->slug) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">السعر الأساسي (ل.س)</label>
                        <input type="number" name="price_syp" class="form-control" value="{{ old('price_syp', $item->price_syp) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">سعر العرض</label>
                        <input type="number" name="sale_price_syp" class="form-control" value="{{ old('sale_price_syp', $item->sale_price_syp) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">وقت التحضير (د)</label>
                        <input type="number" name="eta_min_minutes" class="form-control" value="{{ old('eta_min_minutes', $item->eta_min_minutes) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">صورة (مسار)</label>
                        <input type="text" name="image" class="form-control" value="{{ old('image', $item->image) }}" placeholder="images/express/….jpg">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">التقييم</label>
                        <input type="number" step="0.01" name="rating" class="form-control" value="{{ old('rating', $item->rating) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الترتيب</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $item->sort_order) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="مثلاً: دجاج مشوي على الفحم مع تتبيلة شامية…">{{ old('description', $item->description) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">يُقدَّم مع</label>
                        <input type="text" name="serving_note" class="form-control" value="{{ old('serving_note', $item->serving_note) }}" placeholder="ثوم + بطاطا + مخلل">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المكوّنات</label>
                        <input type="text" name="ingredients" class="form-control" value="{{ old('ingredients', $item->ingredients) }}" placeholder="دجاج، بهارات، زيت زيتون…">
                    </div>

                    <div class="col-12"><hr><h2 class="h5 mb-0">الحصص / الأحجام (مثل المقاس بالملابس)</h2><p class="text-muted small mb-0">نصف فروج، كامل، كيلو… كل خيار بسعره</p></div>
                    @php
                        $variantRows = old('variants', $variants->map(fn($v) => [
                            'label' => $v->label,
                            'unit_label' => $v->unit_label,
                            'price_syp' => $v->price_syp,
                            'sale_price_syp' => $v->sale_price_syp,
                            'is_default' => $v->is_default,
                        ])->all());
                        if (count($variantRows) < 3) {
                            $variantRows = array_pad($variantRows, 3, ['label'=>'','unit_label'=>'','price_syp'=>'','sale_price_syp'=>'','is_default'=>false]);
                        }
                    @endphp
                    @foreach($variantRows as $i => $v)
                        <div class="col-md-3">
                            <label class="form-label">الحصة {{ $i+1 }}</label>
                            <input type="text" name="variants[{{ $i }}][label]" class="form-control" value="{{ $v['label'] ?? '' }}" placeholder="نصف فروج">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">وحدة</label>
                            <input type="text" name="variants[{{ $i }}][unit_label]" class="form-control" value="{{ $v['unit_label'] ?? '' }}" placeholder="نصف">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">السعر</label>
                            <input type="number" name="variants[{{ $i }}][price_syp]" class="form-control" value="{{ $v['price_syp'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">عرض</label>
                            <input type="number" name="variants[{{ $i }}][sale_price_syp]" class="form-control" value="{{ $v['sale_price_syp'] ?? '' }}">
                        </div>
                        <div class="col-md-3 d-flex align-items-end pb-2">
                            <div class="form-check">
                                <input type="hidden" name="variants[{{ $i }}][is_default]" value="0">
                                <input class="form-check-input" type="checkbox" name="variants[{{ $i }}][is_default]" value="1" id="vdef{{ $i }}" @checked(!empty($v['is_default']))>
                                <label class="form-check-label" for="vdef{{ $i }}">افتراضي</label>
                            </div>
                        </div>
                    @endforeach

                    <div class="col-12"><hr><h2 class="h5 mb-0">إضافات / خيارات (مثل اللون بالملابس)</h2><p class="text-muted small mb-0">مجموعة = التوابل أو الإضافات · الخيار = حار / عادي / ثوم إضافي</p></div>
                    @php
                        $extraRows = old('extras', $extras->map(fn($e) => [
                            'group_name' => $e->group_name,
                            'label' => $e->label,
                            'price_delta_syp' => $e->price_delta_syp,
                            'is_default' => $e->is_default,
                        ])->all());
                        if (count($extraRows) < 4) {
                            $extraRows = array_pad($extraRows, 4, ['group_name'=>'','label'=>'','price_delta_syp'=>'','is_default'=>false]);
                        }
                    @endphp
                    @foreach($extraRows as $i => $e)
                        <div class="col-md-3">
                            <label class="form-label">المجموعة</label>
                            <input type="text" name="extras[{{ $i }}][group_name]" class="form-control" value="{{ $e['group_name'] ?? '' }}" placeholder="التوابل">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الخيار</label>
                            <input type="text" name="extras[{{ $i }}][label]" class="form-control" value="{{ $e['label'] ?? '' }}" placeholder="حار">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">فرق السعر</label>
                            <input type="number" name="extras[{{ $i }}][price_delta_syp]" class="form-control" value="{{ $e['price_delta_syp'] ?? '' }}" placeholder="0">
                        </div>
                        <div class="col-md-3 d-flex align-items-end pb-2">
                            <div class="form-check">
                                <input type="hidden" name="extras[{{ $i }}][is_default]" value="0">
                                <input class="form-check-input" type="checkbox" name="extras[{{ $i }}][is_default]" value="1" id="edef{{ $i }}" @checked(!empty($e['is_default']))>
                                <label class="form-check-label" for="edef{{ $i }}">افتراضي</label>
                            </div>
                        </div>
                    @endforeach

                    <div class="col-12 d-flex flex-wrap gap-4">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $item->is_active))>
                            <label class="form-check-label" for="is_active">نشط</label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_offer" value="0">
                            <input class="form-check-input" type="checkbox" name="is_offer" value="1" id="is_offer" @checked(old('is_offer', $item->is_offer))>
                            <label class="form-check-label" for="is_offer">عرض</label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_featured" value="0">
                            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured" @checked(old('is_featured', $item->is_featured))>
                            <label class="form-check-label" for="is_featured">مميّز</label>
                        </div>
                    </div>
                </div>
                <div class="mt-4"><button class="btn btn-primary">حفظ</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
