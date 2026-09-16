@extends('admin.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $category->exists ? 'تعديل تصنيف' : 'تصنيف جديد' }}</h1>
            <p class="text-muted mb-0">طلباتي </p>
        </div>
        <a href="{{ route('admin.express-categories.index') }}" class="btn btn-outline-secondary">رجوع</a>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="post" action="{{ $category->exists ? route('admin.express-categories.update', $category) : route('admin.express-categories.store') }}">
                @csrf
                @if($category->exists) @method('put') @endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الاسم</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', $category->slug) }}" placeholder="اختياري">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">أيقونة</label>
                        <input type="text" name="icon" class="form-control" value="{{ old('icon', $category->icon) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">صورة (مسار)</label>
                        <input type="text" name="image" class="form-control" value="{{ old('image', $category->image) }}" placeholder="images/express/...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الترتيب</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $category->sort_order) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $category->description) }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $category->is_active))>
                            <label class="form-check-label" for="is_active">نشط</label>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
