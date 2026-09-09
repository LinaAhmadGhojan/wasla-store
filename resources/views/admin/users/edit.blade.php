@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4" style="max-width: 960px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">تعديل مستخدم</h1>
                <p class="text-muted mb-0">الحذف ممنوع — عطّلي الحساب مؤقتاً عند الحاجة.</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">رجوع</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form action="{{ route('admin.users.update', $user) }}" method="post">
                    @csrf
                    @method('put')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">الاسم</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الإيميل</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required dir="ltr" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الهاتف</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الدور</label>
                            <select name="role_id" class="form-select" required>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ $role->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">كلمة المرور</label>
                            <input type="password" name="password" class="form-control" placeholder="اتركيها فارغة للإبقاء على الحالية" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">حالة الحساب</label>
                            <select name="is_active" class="form-select" required>
                                <option value="1" @selected(old('is_active', $user->is_active) == 1)>مفعّل</option>
                                <option value="0" @selected(old('is_active', $user->is_active) == 0)>معطّل مؤقتاً</option>
                            </select>
                            <div class="form-text">التعطيل يمنع الدخول فوراً ويلغي جلسات التطبيق.</div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">حفظ</button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h6 text-muted text-uppercase mb-3">أجهزة هذا الحساب</h2>
                @forelse($sightings as $s)
                    <div class="border rounded-3 p-3 mb-2">
                        <strong>{{ $s->device_name ?: 'جهاز بدون اسم' }}</strong>
                        <span class="badge bg-light text-dark">{{ $s->platform ?: '—' }}</span>
                        <div class="small text-muted font-monospace" dir="ltr">hash: {{ \Illuminate\Support\Str::limit($s->token_hash, 16) }}…</div>
                        <div class="small text-muted">آخر ظهور: {{ $s->last_seen_at?->diffForHumans() }}</div>
                    </div>
                @empty
                    <p class="text-muted mb-0">ما في أجهزة مسجّلة بعد (يحتاج تسجيل توكن FCM من التطبيق).</p>
                @endforelse
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h6 text-muted text-uppercase mb-3">حسابات أخرى على نفس الجهاز</h2>
                @forelse($siblings as $sib)
                    <div class="d-flex justify-content-between align-items-center border rounded-3 p-3 mb-2 @if($sib->is_active) border-warning @endif">
                        <div>
                            <strong>{{ $sib->name }}</strong>
                            <div class="small" dir="ltr">{{ $sib->email }}</div>
                            <span class="badge bg-{{ $sib->is_active ? 'success' : 'secondary' }}">
                                {{ $sib->is_active ? 'مفعّل' : 'معطّل' }}
                            </span>
                        </div>
                        <a href="{{ route('admin.users.edit', $sib) }}" class="btn btn-sm btn-outline-primary">فتح</a>
                    </div>
                @empty
                    <p class="text-muted mb-0">ما في حسابات أخرى ظهرت على أجهزة هذا المستخدم.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
