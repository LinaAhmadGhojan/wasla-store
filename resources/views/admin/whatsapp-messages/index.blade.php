@extends('admin.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">رسائل المجموعة</h1>
            <p class="text-muted mb-0">رسائل جاهزة للتحفيز، فتح/إغلاق الطلبية، والحوافز — ضغطة واحدة للإرسال.</p>
        </div>
        <a href="{{ route('admin.whatsapp-messages.create') }}" class="btn btn-primary">+ رسالة جديدة</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(!$gatewayReady)
        <div class="alert alert-warning">
            واتساب غير متصل — <a href="{{ route('admin.whatsapp.connection') }}">اربطيه أولاً</a>.
        </div>
    @elseif(!$canSendToGroup)
        <div class="alert alert-warning">
            المجموعة غير مربوطة —
            <a href="{{ $group ? route('admin.whatsapp-groups.edit', $group) : route('admin.whatsapp-groups.index') }}">
                اربطي «{{ $group?->name ?? 'المجموعة' }}»
            </a>
        </div>
    @else
        <div class="alert alert-info mb-4">
            <strong>الإرسال إلى:</strong> مجموعة «{{ $group->name }}» —
            اضغطي <strong>إرسال</strong> بجانب أي رسالة (ضغطة واحدة، بدون تأكيد).
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="text-muted small">فلترة:</span>
                <button type="button" class="btn btn-sm btn-outline-secondary wa-filter active" data-filter="all">الكل</button>
                @foreach($categories as $key => $label)
                    <button type="button" class="btn btn-sm btn-outline-secondary wa-filter" data-filter="{{ $key }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>العنوان</th>
                        <th>النوع</th>
                        <th>معاينة</th>
                        <th>آخر إرسال</th>
                        <th class="text-end">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($messages as $msg)
                        <tr class="{{ !$msg->is_active ? 'table-secondary' : '' }}" data-category="{{ $msg->category }}">
                            <td>
                                <strong>{{ $msg->title }}</strong>
                                @if(!$msg->is_active)
                                    <span class="badge bg-secondary ms-1">معطّلة</span>
                                @endif
                                @if($msg->send_count > 0)
                                    <span class="text-muted small d-block">أُرسلت {{ $msg->send_count }} مرة</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $msg->categoryBadgeClass() }}">{{ $msg->categoryLabel() }}</span>
                            </td>
                            <td>
                                <details class="small">
                                    <summary class="text-primary" style="cursor:pointer">عرض النص</summary>
                                    <pre class="mt-2 mb-0 p-2 bg-light rounded small" style="white-space:pre-wrap">{{ $msg->body }}</pre>
                                </details>
                            </td>
                            <td class="text-muted small">
                                @if($msg->last_sent_at)
                                    {{ $msg->last_sent_at->diffForHumans() }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if($gatewayReady && $canSendToGroup && $msg->is_active)
                                    <form action="{{ route('admin.whatsapp-messages.send', $msg) }}" method="post" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">إرسال</button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-sm btn-success" disabled>إرسال</button>
                                @endif
                                <a href="{{ route('admin.whatsapp-messages.edit', $msg) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                                <form action="{{ route('admin.whatsapp-messages.destroy', $msg) }}" method="post" class="d-inline">
                                    @csrf @method('delete')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف هذه الرسالة؟')">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                ما في رسائل بعد.
                                <a href="{{ route('admin.whatsapp-messages.create') }}">أضيفي رسالة</a>
                                أو شغّلي:
                                <code>php artisan db:seed --class=WhatsappBroadcastSeeder</code>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-4 border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">💡 أفكار رسائل تحفيزية</h2>
            <div class="row g-3 small text-muted">
                <div class="col-md-4">🎁 مكافأة إضافة أعضاء للمجموعة</div>
                <div class="col-md-4">⏰ تذكير آخر فرصة قبل إغلاق الطلبية</div>
                <div class="col-md-4">⭐ شكر أسبوعي + عدد الطلبات</div>
                <div class="col-md-4">🔥 تحدي مشاركة منتج مع أصدقاء</div>
                <div class="col-md-4">📦 تحديث وصول شحنة جديدة</div>
                <div class="col-md-4">👋 ترحيب بالأعضاء الجدد</div>
            </div>
            <p class="small text-muted mb-0 mt-3">
                متغيرات: <code>{group_name}</code> <code>{store}</code> <code>{date}</code> <code>{time}</code>
            </p>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.wa-filter').forEach((btn) => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.wa-filter').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const filter = btn.dataset.filter;
        document.querySelectorAll('tbody tr[data-category]').forEach((row) => {
            row.style.display = (filter === 'all' || row.dataset.category === filter) ? '' : 'none';
        });
    });
});
</script>
@endsection
