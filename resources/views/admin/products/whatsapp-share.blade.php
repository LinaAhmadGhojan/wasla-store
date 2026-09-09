@extends('admin.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1">إرسال على واتساب</h1>
            <p class="text-muted mb-0">{{ $product->name }}</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">← Products</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($product->isWhatsappPublished())
        <div class="alert alert-success">
            ✅ <strong>منشور على واتساب</strong>
            — {{ $product->whatsapp_published_at->format('Y-m-d H:i') }}
            ({{ $product->whatsapp_published_target === 'group' ? 'مجموعة' : 'رقم' }})
        </div>
    @endif

    @if(!$gatewayReady)
        <div class="alert alert-warning">
            خدمة الإرسال التلقائي غير جاهزة.
            @if($gatewayError)
                <span class="d-block small mt-1">{{ $gatewayError }}</span>
            @endif
            <a href="{{ route('admin.whatsapp.connection') }}" class="alert-link">ربط واتساب (مسح QR)</a>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            @if($product->image)
                <img src="{{ str_starts_with($product->image, 'http') ? $product->image : asset($product->image) }}" class="img-fluid rounded mb-3" alt="">
            @endif
            <p class="text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($product->description ?? ''), 200) }}</p>
            <p><strong>{{ number_format($product->price, 2) }} AED</strong></p>
        </div>
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <h2 class="h6 mb-3">وجهة الإرسال</h2>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="send_target" id="target_phone" value="phone"
                            @checked(old('target', $defaultSendTarget) === 'phone')>
                        <label class="form-check-label" for="target_phone">
                            📱 رقمي
                            @if($recipientPhone)
                                <span class="text-muted" dir="ltr">(+{{ $recipientPhone }})</span>
                            @else
                                <span class="text-danger small">— غير مضبوط</span>
                            @endif
                        </label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="send_target" id="target_group" value="group"
                            @checked(old('target', $defaultSendTarget) === 'group')
                            @disabled(!$canSendToGroup)>
                        <label class="form-check-label" for="target_group">
                            👥 المجموعة
                            @if($group)
                                «{{ $group->name }}»
                            @endif
                            @if(!$canSendToGroup)
                                <span class="text-danger small">— <a href="{{ $group ? route('admin.whatsapp-groups.edit', $group) : route('admin.whatsapp-groups.index') }}">اربطي المجموعة</a></span>
                            @endif
                        </label>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6">معاينة الرسالة</h2>
                    <pre id="waMessage" class="bg-light p-3 rounded small" style="white-space: pre-wrap;">{{ $message }}</pre>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        @if($gatewayReady && ($recipientPhone || $canSendToGroup))
                            <form action="{{ route('admin.products.whatsapp.send', $product) }}" method="post" class="d-inline" id="sendForm">
                                @csrf
                                <input type="hidden" name="target" id="targetInput" value="{{ old('target', $defaultSendTarget) }}">
                                <button type="submit" class="btn btn-success btn-lg" id="sendBtn">📲 إرسال مباشر</button>
                            </form>
                        @endif
                        @if($shareUrl)
                            <a href="{{ $shareUrl }}" target="_blank" rel="noopener" class="btn btn-outline-success">
                                فتح واتساب يدوياً (رقمي)
                            </a>
                        @endif
                        <button type="button" class="btn btn-outline-secondary" id="copyBtn">نسخ الرسالة</button>
                    </div>

                    <div class="alert alert-warning small mt-3 mb-0">
                        <strong>هل في ضرر؟</strong> الإرسال التلقائي يستخدم WhatsApp Web (غير رسمي).
                        <ul class="mb-0 mt-1">
                            <li>استخدمي <strong>رقم مخصص للمتجر</strong> — مو رقمك الشخصي الأساسي.</li>
                            <li>لا ترسلي كثير رسائل بوقت قصير (spam) — ممكن <strong>حظر مؤقت</strong>.</li>
                            <li>للمجموعة: الحساب المربوط لازم يكون <strong>عضو</strong> فيها.</li>
                            <li>رابط الدعوة وحده ما بكفي — لازم ربط <strong>Group Chat ID</strong> من إعدادات المجموعة.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('input[name="send_target"]').forEach((radio) => {
    radio.addEventListener('change', () => {
        document.getElementById('targetInput').value = radio.value;
    });
});

document.getElementById('copyBtn')?.addEventListener('click', () => {
    const text = document.getElementById('waMessage').textContent;
    navigator.clipboard.writeText(text).then(() => alert('تم النسخ'));
});
</script>
@endsection
