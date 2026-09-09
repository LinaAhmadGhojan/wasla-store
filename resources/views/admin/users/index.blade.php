@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">المستخدمون</h1>
                <p class="text-muted mb-0">تعطيل مؤقت مسموح — حذف الحساب ممنوع.</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">إنشاء مستخدم</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
                <form action="{{ route('admin.users.index') }}" method="get" class="d-flex gap-2 align-items-center flex-wrap">
                    <input type="search" name="q" value="{{ old('q', $search) }}" class="form-control form-control-sm" placeholder="بحث بالاسم أو الإيميل أو الهاتف" />
                    <label class="form-check form-check-inline mb-0">
                        <input type="checkbox" class="form-check-input" name="shared_device" value="1" @checked($filterShared ?? false) onchange="this.form.submit()" />
                        <span class="form-check-label">أجهزة بحسابات متعددة</span>
                    </label>
                    <button class="btn btn-secondary btn-sm">بحث</button>
                </form>
                <div class="text-muted">عرض {{ $users->count() }} من {{ $users->total() }}</div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>الاسم</th>
                            <th>الإيميل</th>
                            <th>الهاتف</th>
                            <th>الدور</th>
                            <th>الحالة</th>
                            <th>أجهزة مشتركة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            @php $siblings = $sharedByUser[$user->id] ?? collect(); @endphp
                            <tr class="@if($siblings->isNotEmpty()) table-warning @endif">
                                <td>{{ $user->name }}</td>
                                <td dir="ltr">{{ $user->email }}</td>
                                <td>{{ $user->phone ?? '-' }}</td>
                                <td>{{ $user->role?->label ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-{{ $user->is_active ? 'success' : 'secondary' }}">
                                        {{ $user->is_active ? 'مفعّل' : 'معطّل مؤقتاً' }}
                                    </span>
                                </td>
                                <td>
                                    @if($siblings->isNotEmpty())
                                        <span class="badge bg-danger">{{ $siblings->count() }} حساب آخر</span>
                                        <div class="small text-muted mt-1">
                                            @foreach($siblings->take(3) as $sib)
                                                <div>{{ $sib->email }} @unless($sib->is_active)<em>(معطّل)</em>@endunless</div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">تعديل / تعطيل</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">لا يوجد مستخدمون.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $users->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
