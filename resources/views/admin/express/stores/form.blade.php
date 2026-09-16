@extends('admin.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $store->exists ? 'تعديل متجر' : 'متجر لقمة ' }}</h1>
            <p class="text-muted mb-0">نفس أسلوب إدارة متاجر وصلة — منفصل للقمة </p>
        </div>
        <a href="{{ route('admin.express-stores.index') }}" class="btn btn-outline-secondary">رجوع</a>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="post" action="{{ $store->exists ? route('admin.express-stores.update', $store) : route('admin.express-stores.store') }}">
                @csrf
                @if($store->exists) @method('put') @endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">اسم المتجر</label>
                        <input type="text" name="store_name" class="form-control" value="{{ old('store_name', $store->store_name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', $store->slug) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">التصنيف</label>
                        <select name="express_category_id" class="form-select">
                            <option value="">—</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('express_category_id', $store->express_category_id) == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المالك (اختياري)</label>
                        <select name="owner_id" class="form-select">
                            <option value="">—</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(old('owner_id', $store->owner_id) == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">نوع المطبخ</label>
                        <input type="text" name="cuisine" class="form-control" value="{{ old('cuisine', $store->cuisine) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المنطقة</label>
                        <input type="text" name="area" class="form-control" value="{{ old('area', $store->area) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select" required>
                            @foreach(['pending','active','suspended','closed'] as $st)
                                <option value="{{ $st }}" @selected(old('status', $store->status) === $st)>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">وقت التوصيل من (د)</label>
                        <input type="number" name="eta_min_minutes" class="form-control" value="{{ old('eta_min_minutes', $store->eta_min_minutes) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">وقت التوصيل إلى (د)</label>
                        <input type="number" name="eta_max_minutes" class="form-control" value="{{ old('eta_max_minutes', $store->eta_max_minutes) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رسوم التوصيل (ل.س)</label>
                        <input type="number" name="delivery_fee_syp" class="form-control" value="{{ old('delivery_fee_syp', $store->delivery_fee_syp) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">التقييم</label>
                        <input type="number" step="0.01" name="rating" class="form-control" value="{{ old('rating', $store->rating) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">العمولة %</label>
                        <input type="number" step="0.01" name="commission_rate" class="form-control" value="{{ old('commission_rate', $store->commission_rate) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">صورة / بانر (مسار)</label>
                        <input type="text" name="banner" class="form-control" value="{{ old('banner', $store->banner) }}" placeholder="images/express/store-….jpg">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">شعار (مسار)</label>
                        <input type="text" name="logo" class="form-control" value="{{ old('logo', $store->logo) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">العنوان</label>
                        <input type="text" name="street_address" class="form-control" value="{{ old('street_address', $store->street_address) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $store->description) }}</textarea>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-4">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_open" value="0">
                            <input class="form-check-input" type="checkbox" name="is_open" value="1" id="is_open" @checked(old('is_open', $store->is_open))>
                            <label class="form-check-label" for="is_open">مفتوح</label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_featured" value="0">
                            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured" @checked(old('is_featured', $store->is_featured))>
                            <label class="form-check-label" for="is_featured">مميّز</label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_verified" value="0">
                            <input class="form-check-input" type="checkbox" name="is_verified" value="1" id="is_verified" @checked(old('is_verified', $store->is_verified))>
                            <label class="form-check-label" for="is_verified">موثّق</label>
                        </div>
                    </div>
                </div>
                <div class="mt-4"><button class="btn btn-primary">حفظ</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
