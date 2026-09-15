@extends('admin.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1">تصنيفات وصلة السريعة</h1>
            <p class="text-muted mb-0">مطاعم، مخابز، مشروبات، بقالة…</p>
        </div>
        <a href="{{ route('admin.express-categories.create') }}" class="btn btn-primary">تصنيف جديد</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الاسم</th>
                        <th>Slug</th>
                        <th>الترتيب</th>
                        <th>الحالة</th>
                        <th class="text-end">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td><code>{{ $category->slug }}</code></td>
                            <td>{{ $category->sort_order }}</td>
                            <td>
                                <span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}">
                                    {{ $category->is_active ? 'نشط' : 'متوقف' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.express-categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                                <form action="{{ route('admin.express-categories.destroy', $category) }}" method="post" class="d-inline">
                                    @csrf @method('delete')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف التصنيف؟')">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4">لا تصنيفات بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $categories->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
