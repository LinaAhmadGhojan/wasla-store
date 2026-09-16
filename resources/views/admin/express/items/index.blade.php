@extends('admin.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">أطباق لقمة </h1>
            <p class="text-muted mb-0">قائمة المنتجات/الأطباق للمتاجر السريعة</p>
        </div>
        <a href="{{ route('admin.express-items.create', array_filter(['store_id' => $storeId])) }}" class="btn btn-primary">طبق جديد</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <form class="mb-3 row g-2 align-items-end" method="get">
        <div class="col-md-4">
            <label class="form-label">بحث</label>
            <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="اسم الطبق…">
        </div>
        <div class="col-md-4">
            <label class="form-label">المتجر</label>
            <select name="store_id" class="form-select">
                <option value="">الكل</option>
                @foreach($stores as $s)
                    <option value="{{ $s->id }}" @selected((string)$storeId === (string)$s->id)>{{ $s->store_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100">تصفية</button></div>
    </form>
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الطبق</th>
                        <th>المتجر</th>
                        <th>السعر</th>
                        <th>عرض</th>
                        <th>نشط</th>
                        <th class="text-end">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td>{{ $item->store?->store_name }}</td>
                            <td>{{ number_format($item->effective_price) }} ل.س</td>
                            <td>{{ $item->is_offer ? 'نعم' : 'لا' }}</td>
                            <td>{{ $item->is_active ? 'نعم' : 'لا' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.express-items.edit', $item) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                                <form action="{{ route('admin.express-items.destroy', $item) }}" method="post" class="d-inline">
                                    @csrf @method('delete')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف؟')">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4">لا أطباق بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $items->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
