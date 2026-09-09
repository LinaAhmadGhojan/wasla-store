@extends('admin.layout')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">دفعة شراء جديدة</h1>

    <form action="{{ route('admin.procurement-batches.store') }}" method="post" class="card shadow-sm border-0" style="max-width:560px">
        @csrf
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">عنوان الدفعة *</label>
                <input type="text" name="title" class="form-control" required
                    placeholder="مثلاً: طلبية SHEIN — 18 آب" value="{{ old('title') }}">
            </div>
            <div class="mb-3">
                <label class="form-label">ملاحظات</label>
                <textarea name="notes" rows="3" class="form-control" placeholder="اختياري">{{ old('notes') }}</textarea>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button type="submit" class="btn btn-primary">إنشاء</button>
            <a href="{{ route('admin.procurement-batches.index') }}" class="btn btn-secondary">إلغاء</a>
        </div>
    </form>
</div>
@endsection
