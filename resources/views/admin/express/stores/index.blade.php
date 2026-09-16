@extends('admin.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">متاجر طلباتي </h1>
            <p class="text-muted mb-0">إضافة وإدارة مطاعم ومتاجر التوصيل السريع</p>
        </div>
        <a href="{{ route('admin.express-stores.create') }}" class="btn btn-primary">متجر جديد</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <form class="mb-3" method="get">
        <div class="input-group" style="max-width:360px">
            <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="بحث باسم المتجر…">
            <button class="btn btn-outline-secondary">بحث</button>
        </div>
    </form>
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>المتجر</th>
                        <th>التصنيف</th>
                        <th>المطبخ</th>
                        <th>الحالة</th>
                        <th>مفتوح</th>
                        <th class="text-end">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stores as $store)
                        <tr>
                            <td>
                                <strong>{{ $store->store_name }}</strong>
                                <div class="small text-muted">{{ $store->eta_label }} · ★ {{ $store->rating }}</div>
                            </td>
                            <td>{{ $store->category?->name ?? '—' }}</td>
                            <td>{{ $store->cuisine ?: '—' }}</td>
                            <td><span class="badge bg-{{ $store->status === 'active' ? 'success' : ($store->status === 'pending' ? 'warning' : 'secondary') }}">{{ $store->status }}</span></td>
                            <td>{{ $store->is_open ? 'نعم' : 'لا' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.express-items.index', ['store_id' => $store->id]) }}" class="btn btn-sm btn-outline-secondary">أطباق</a>
                                <a href="{{ route('admin.express-stores.edit', $store) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                                <form action="{{ route('admin.express-stores.destroy', $store) }}" method="post" class="d-inline">
                                    @csrf @method('delete')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف المتجر؟')">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4">لا متاجر بعد — أضيفي من «متجر جديد» أو شغّلي الـ seeder.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $stores->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
