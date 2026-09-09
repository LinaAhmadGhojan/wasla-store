@extends('admin.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1">ربط واتساب</h1>
            <p class="text-muted mb-0">امسحي QR مرة واحدة لتفعيل الإرسال المباشر من لوحة التحكم.</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">← Products</a>
    </div>

    @if(!$gatewayEnabled)
        <div class="alert alert-warning">
            خدمة واتساب غير مفعّلة. تأكدي من <code>WHATSAPP_GATEWAY_URL</code> في ملف <code>.env</code>.
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div id="statusBadge" class="mb-3">
                @if($status['ready'] ?? false)
                    <span class="badge bg-success fs-6">متصل — جاهز للإرسال</span>
                @else
                    <span class="badge bg-secondary fs-6">غير متصل</span>
                @endif
            </div>

            <div id="loadingWrap" class="text-center mb-3 text-muted">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                <span id="loadingText">جاري تحضير واتساب ويب…</span>
            </div>

            <div id="qrWrap" class="text-center mb-3" style="display:none">
                <p class="text-muted">افتحي واتساب على جوالك → الأجهزة المرتبطة → ربط جهاز → امسحي الرمز:</p>
                <img id="qrImage" src="" alt="WhatsApp QR" class="img-fluid border rounded p-2 bg-white" style="max-width:280px">
            </div>

            <p id="errorText" class="text-danger small d-none"></p>
            <p id="offlineText" class="text-warning small d-none">خدمة whatsapp-gateway غير شغّالة — شغّلي: <code>cd whatsapp-gateway && npm start</code></p>

            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-outline-primary btn-sm" id="resetBtn">🔄 إعادة QR (جلسة جديدة)</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshBtn">تحديث</button>
            </div>

            <div class="alert alert-info mb-0">
                <strong>الجوال ≠ لوحة التحكم.</strong>
                الجهاز المرتبط على الموبايل يعني الجلسة لسا موجودة عند واتساب.
                «متصل» هنا يعني برنامج وصلة فتح واتساب ويب وقدر يرسل.
                إذا كروم انقطع أو البوابة انقفلت، الرقم بيضل مربوط بس الإرسال واقف.
            </div>
        </div>
    </div>
</div>

<script>
const statusUrl = @json(route('admin.whatsapp.status'));
const resetUrl = @json(route('admin.whatsapp.reset'));
const csrf = @json(csrf_token());

async function pollStatus() {
    const badge = document.getElementById('statusBadge');
    const qrWrap = document.getElementById('qrWrap');
    const qrImage = document.getElementById('qrImage');
    const errorText = document.getElementById('errorText');
    const loadingWrap = document.getElementById('loadingWrap');
    const offlineText = document.getElementById('offlineText');

    try {
        const res = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        offlineText.classList.add('d-none');

        if (data.ready) {
            badge.innerHTML = '<span class="badge bg-success fs-6">متصل — جاهز للإرسال</span>';
            qrWrap.style.display = 'none';
            loadingWrap.style.display = 'none';
        } else if (data.qr) {
            badge.innerHTML = '<span class="badge bg-warning text-dark fs-6">امسحي QR</span>';
            qrWrap.style.display = 'block';
            qrImage.src = data.qr;
            loadingWrap.style.display = 'none';
        } else if (data.authenticated || data.initializing) {
            badge.innerHTML = '<span class="badge bg-info text-dark fs-6">مربوط على الجوال — عم نحمّل واتساب ويب</span>';
            qrWrap.style.display = 'none';
            loadingWrap.style.display = 'block';
            const pct = data.loading_percent ?? data.loadingPercent;
            document.getElementById('loadingText').textContent = pct
                ? 'عم نحمّل واتساب ويب… ' + pct + '%'
                : 'الجلسة مربوطة على الرقم، بس الإرسال ما بيشتغل إلا بعد ما تصير الحالة «متصل».';
        } else {
            badge.innerHTML = '<span class="badge bg-secondary fs-6">غير متصل</span>';
            qrWrap.style.display = 'none';
            loadingWrap.style.display = 'none';
        }

        if (data.error) {
            errorText.textContent = data.error;
            errorText.classList.remove('d-none');
        } else {
            errorText.classList.add('d-none');
        }
    } catch (e) {
        offlineText.classList.remove('d-none');
        loadingWrap.style.display = 'none';
    }
}

document.getElementById('refreshBtn')?.addEventListener('click', pollStatus);
document.getElementById('resetBtn')?.addEventListener('click', async () => {
    if (!confirm('إعادة الجلسة؟ رح يظهر QR جديد.')) return;
    await fetch(resetUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
    });
    document.getElementById('loadingWrap').style.display = 'block';
    setTimeout(pollStatus, 2000);
});

pollStatus();
setInterval(pollStatus, 3000);
</script>
@endsection
