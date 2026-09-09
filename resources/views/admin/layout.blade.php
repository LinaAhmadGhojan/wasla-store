<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'وصلة') }} — لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        body {
            margin: 0;
            background: #eef7f9;
            color: #132f37;
            font-family: 'Segoe UI', Tahoma, Arial, system-ui, sans-serif;
            direction: rtl;
        }
        /* ── Sidebar ─────────────────────────────────────────── */
        .admin-sidebar {
            width: 260px;
            min-height: 100vh;
            background: linear-gradient(180deg, #1a6e7e 0%, #2a8a9a 100%);
            color: #e6fbff;
            border-left: 1px solid rgba(255,255,255,0.08);
            flex-shrink: 0;
            overflow-y: auto;
        }
        .sidebar-logo {
            padding: 1.5rem 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .sidebar-logo h2 {
            color: #fff;
            font-size: 1.15rem;
            margin: 0 0 0.2rem;
            font-weight: 700;
        }
        .sidebar-logo p {
            color: #78cde3;
            font-size: 0.78rem;
            margin: 0;
        }
        .sidebar-nav {
            padding: 0.75rem 0.85rem 2rem;
        }
        /* Group toggle button */
        .sidebar-group-toggle {
            display: flex;
            align-items: center;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 0.4rem;
            width: 100%;
            background: none;
            border: none;
            color: #4a9fb8;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.13em;
            text-transform: uppercase;
            padding: 1.2rem 0.5rem 0.4rem;
            cursor: pointer;
            transition: color 0.15s;
        }
        .sidebar-group-toggle:hover { color: #7dd4ea; }
        .toggle-arrow {
            width: 1rem;
            height: 1rem;
            transition: transform 0.2s ease;
            transform: rotate(-90deg);
            flex-shrink: 0;
        }
        .sidebar-group-toggle.open .toggle-arrow {
            transform: rotate(0deg);
        }
        /* Collapsible group */
        .sidebar-group {
            display: none;
            overflow: hidden;
        }
        .sidebar-group.open {
            display: block;
        }
        .sidebar-nav a {
            display: block;
            color: #c8e8f0;
            text-decoration: none;
            padding: 0.62rem 0.85rem;
            border-radius: 0.65rem;
            margin-bottom: 0.15rem;
            font-size: 0.9rem;
            transition: background 0.15s, color 0.15s;
        }
        .sidebar-nav a:hover {
            background: rgba(0,157,189,0.18);
            color: #fff;
        }
        .sidebar-nav a.active {
            background: rgba(0,157,189,0.28);
            color: #fff;
            font-weight: 600;
        }
        /* ── Main ────────────────────────────────────────────── */
        .admin-main {
            flex: 1;
            min-width: 0;
            background: #eef7f9;
        }
        .admin-topbar {
            background: #1c7282;
            border-bottom: 1px solid rgba(15,90,107,0.1);
            padding: 0.85rem 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 40;
        }
        .admin-topbar .page-title {
            font-size: 1rem;
            font-weight: 600;
            color: white;
            margin: 0;
        }
        .admin-alerts { position: relative; }
        .alert-bell {
            border: 1px solid rgba(255,255,255,.35);
            background: rgba(255,255,255,.12);
            color: #fff;
            border-radius: 999px;
            width: 44px; height: 44px;
            font-size: 1.15rem;
            position: relative;
            cursor: pointer;
        }
        .alert-badge {
            position: absolute; top: -4px; left: -4px;
            min-width: 20px; height: 20px; padding: 0 5px;
            border-radius: 999px; background: #ff5252; color: #fff;
            font-size: 0.7rem; font-weight: 800;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .alert-panel {
            position: absolute; top: calc(100% + 8px); left: 0;
            width: min(380px, 92vw);
            background: #fff;
            border: 1px solid rgba(28,114,130,.15);
            border-radius: 1rem;
            box-shadow: 0 12px 40px rgba(19,47,55,.18);
            overflow: hidden;
            color: #132f37;
        }
        .alert-panel-head, .alert-panel-foot {
            display: flex; align-items: center; justify-content: space-between; gap: .5rem;
            padding: .75rem 1rem;
            background: #f5fbfc;
            border-bottom: 1px solid rgba(28,114,130,.08);
        }
        .alert-panel-foot {
            border-bottom: 0;
            border-top: 1px solid rgba(28,114,130,.08);
            flex-wrap: wrap;
        }
        .alert-panel-body { max-height: 360px; overflow: auto; }
        .alert-item {
            display: block; text-align: right; text-decoration: none; color: inherit;
            padding: .85rem 1rem; border-bottom: 1px solid rgba(28,114,130,.06);
        }
        .alert-item:hover { background: #eef8f9; }
        .alert-item.unread { background: #f0fafb; }
        .alert-item .t { font-weight: 800; color: #0b3d44; }
        .alert-item .b { color: #4d6b72; font-size: .86rem; margin-top: .2rem; }
        .alert-item .when { color: #7a949a; font-size: .75rem; margin-top: .25rem; }
        .admin-content {
            padding: 2rem 2.5rem;
        }
        /* ── Cards ───────────────────────────────────────────── */
        .card { border-radius: 1rem !important; }
        .admin-card {
            background: #ffffff;
            border: 1px solid rgba(15,90,107,0.08);
            border-radius: 1.25rem;
            box-shadow: 0 4px 24px rgba(15,90,107,0.07);
        }
        .text-muted { color: #6c7d84 !important; }
        /* ── Fix form-switch in RTL ──────────────────────────── */
        .form-check.form-switch {
            padding-right: 0;
            padding-left: 0;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        .form-check.form-switch .form-check-input {
            float: none;
            margin: 0;
            flex-shrink: 0;
            order: -1; /* toggle on the LEFT side of label in RTL = visually right */
        }
        .form-check.form-switch .form-check-label {
            flex: 1;
            cursor: pointer;
        }
        /* ── Responsive ──────────────────────────────────────── */
        @media (max-width: 768px) {
            .admin-sidebar { display: none; }
            .admin-content { padding: 1rem; }
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <div class="d-flex min-vh-100">

            {{-- السايدبار — يمين الشاشة --}}
            <aside class="admin-sidebar">
                <div class="sidebar-logo">
                    <h2>وصلة — لوحة التحكم</h2>
                    <p>إدارة المتجر والطلبات</p>
                </div>

                @php
                    // Detect active section to auto-expand it
                    $activeSection = match(true) {
                        request()->routeIs('admin.products.*','admin.categories.*','admin.brands.*','admin.attributes.*','admin.vendors.*') => 'catalog',
                        request()->routeIs('admin.orders.*','admin.purchase-requests.*','admin.procurement-batches.*','admin.external-platforms.*','admin.exchange-rate.*') => 'orders',
                        request()->routeIs('admin.whatsapp.*','admin.whatsapp-messages.*','admin.whatsapp-groups.*') => 'whatsapp',
                        request()->routeIs('admin.users.*','admin.settings.*') => 'accounts',
                        default => null,
                    };
                @endphp

                <nav class="sidebar-nav" id="sidebarNav">
                    <a href="{{ route('admin.dashboard') }}" class="@if(request()->routeIs('admin.dashboard')) active @endif">
                        🏠 الرئيسية
                    </a>

                    {{-- ── الكتالوج ── --}}
                    <button class="sidebar-group-toggle @if($activeSection==='catalog') open @endif" data-target="grp-catalog">
                        <span>الكتالوج</span>
                        <svg class="toggle-arrow" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                    </button>
                    <div class="sidebar-group @if($activeSection==='catalog') open @endif" id="grp-catalog">
                        <a href="{{ route('admin.products.index') }}" class="@if(request()->routeIs('admin.products.*')) active @endif">📦 المنتجات</a>
                        <a href="{{ route('admin.categories.index') }}" class="@if(request()->routeIs('admin.categories.*')) active @endif">🗂 الفئات</a>
                        <a href="{{ route('admin.brands.index') }}" class="@if(request()->routeIs('admin.brands.*')) active @endif">🏷 الماركات</a>
                        <a href="{{ route('admin.attributes.index') }}" class="@if(request()->routeIs('admin.attributes.*')) active @endif">⚙️ الخصائص</a>
                        <a href="{{ route('admin.vendors.index') }}" class="@if(request()->routeIs('admin.vendors.*')) active @endif">🏪 المتاجر</a>
                    </div>

                    {{-- ── الطلبات ── --}}
                    <button class="sidebar-group-toggle @if($activeSection==='orders') open @endif" data-target="grp-orders">
                        <span>الطلبات</span>
                        <svg class="toggle-arrow" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                    </button>
                    <div class="sidebar-group @if($activeSection==='orders') open @endif" id="grp-orders">
                        <a href="{{ route('admin.orders.index') }}" class="@if(request()->routeIs('admin.orders.index') || request()->routeIs('admin.orders.unified')) active @endif">📋 كل الطلبات</a>
                        <a href="{{ route('admin.orders.local') }}" class="@if(request()->routeIs('admin.orders.local') || request()->routeIs('admin.orders.show')) active @endif">🧾 طلبات وصلة (تفصيل)</a>
                        <a href="{{ route('admin.purchase-requests.index') }}" class="@if(request()->routeIs('admin.purchase-requests.*')) active @endif">🛍 شراء خارجي (تفصيل)</a>
                        <a href="{{ route('admin.procurement-batches.index') }}" class="@if(request()->routeIs('admin.procurement-batches.*')) active @endif">📋 دفعات الشراء</a>
                        <a href="{{ route('admin.external-platforms.index') }}" class="@if(request()->routeIs('admin.external-platforms.*')) active @endif">🌐 المنصات الخارجية</a>
                        <a href="{{ route('admin.exchange-rate.edit') }}" class="@if(request()->routeIs('admin.exchange-rate.*')) active @endif">💱 سعر الصرف</a>
                    </div>

                    {{-- ── واتساب ── --}}
                    <button class="sidebar-group-toggle @if($activeSection==='whatsapp') open @endif" data-target="grp-whatsapp">
                        <span>واتساب</span>
                        <svg class="toggle-arrow" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                    </button>
                    <div class="sidebar-group @if($activeSection==='whatsapp') open @endif" id="grp-whatsapp">
                        <a href="{{ route('admin.whatsapp.connection') }}" class="@if(request()->routeIs('admin.whatsapp.connection')) active @endif">📱 اتصال واتساب</a>
                        <a href="{{ route('admin.whatsapp-messages.index') }}" class="@if(request()->routeIs('admin.whatsapp-messages.*')) active @endif">💬 رسائل المجموعة</a>
                        <a href="{{ route('admin.whatsapp-groups.index') }}" class="@if(request()->routeIs('admin.whatsapp-groups.*')) active @endif">👥 قوالب المجموعات</a>
                    </div>

                    {{-- ── الحسابات والإعدادات ── --}}
                    <button class="sidebar-group-toggle @if($activeSection==='accounts') open @endif" data-target="grp-accounts">
                        <span>الحسابات والإعدادات</span>
                        <svg class="toggle-arrow" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                    </button>
                    <div class="sidebar-group @if($activeSection==='accounts') open @endif" id="grp-accounts">
                        <a href="{{ route('admin.users.index') }}" class="@if(request()->routeIs('admin.users.*')) active @endif">👤 المستخدمون</a>
                        <a href="{{ route('admin.settings.index') }}" class="@if(request()->routeIs('admin.settings.*')) active @endif">⚙️ الإعدادات</a>
                    </div>
                </nav>

                <script>
                document.querySelectorAll('.sidebar-group-toggle').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var targetId = this.getAttribute('data-target');
                        var group = document.getElementById(targetId);
                        var isOpen = this.classList.contains('open');
                        // Close all
                        document.querySelectorAll('.sidebar-group-toggle').forEach(function(b) { b.classList.remove('open'); });
                        document.querySelectorAll('.sidebar-group').forEach(function(g) { g.classList.remove('open'); });
                        // Toggle clicked
                        if (!isOpen) {
                            this.classList.add('open');
                            group.classList.add('open');
                        }
                    });
                });
                </script>
            </aside>

            {{-- المحتوى الرئيسي --}}
            <main class="admin-main">
                <div class="admin-topbar">
                    <span class="page-title">{{ $pageTitle ?? 'وصلة — لوحة التحكم' }}</span>
                    <div class="admin-alerts" id="adminAlerts">
                        <button type="button" class="alert-bell" id="alertBellBtn" title="الإشعارات" aria-label="الإشعارات">
                            🔔
                            <span class="alert-badge d-none" id="alertBadge">0</span>
                        </button>
                        <div class="alert-panel d-none" id="alertPanel">
                            <div class="alert-panel-head">
                                <strong>قصص الإشعارات</strong>
                                <button type="button" class="btn btn-sm btn-link" id="alertMarkAll">تعليم الكل كمقروء</button>
                            </div>
                            <div class="alert-panel-body" id="alertList">
                                <div class="text-muted small p-3">لا إشعارات بعد.</div>
                            </div>
                            <div class="alert-panel-foot">
                                <label class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="alertTtsToggle" checked>
                                    <span class="form-check-label">نطق الطلبات الجديدة (TTS)</span>
                                </label>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="alertEnableSound">تفعيل الصوت / إشعار المتصفح</button>
                            </div>
                        </div>
                    </div>
                </div>
                <section class="admin-content">
                    @yield('content')
                </section>
            </main>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script>
    (function () {
        const pollUrl = @json(route('admin.notifications.poll'));
        const readAllUrl = @json(route('admin.notifications.read-all'));
        const readUrlTpl = @json(url('/admin/notifications/__ID__/read'));
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

        const bellBtn = document.getElementById('alertBellBtn');
        const panel = document.getElementById('alertPanel');
        const list = document.getElementById('alertList');
        const badge = document.getElementById('alertBadge');
        const ttsToggle = document.getElementById('alertTtsToggle');
        const enableBtn = document.getElementById('alertEnableSound');
        const markAllBtn = document.getElementById('alertMarkAll');

        if (!bellBtn || !panel) return;

        const LS_LAST = 'wasla_admin_alert_last_id';
        const LS_TTS = 'wasla_admin_tts';
        let lastId = parseInt(localStorage.getItem(LS_LAST) || '0', 10) || 0;
        let bootstrapped = false;
        let soundUnlocked = false;

        if (localStorage.getItem(LS_TTS) === '0') ttsToggle.checked = false;
        ttsToggle?.addEventListener('change', () => {
            localStorage.setItem(LS_TTS, ttsToggle.checked ? '1' : '0');
        });

        bellBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            panel.classList.toggle('d-none');
        });
        document.addEventListener('click', (e) => {
            if (!panel.contains(e.target) && e.target !== bellBtn) {
                panel.classList.add('d-none');
            }
        });

        function speak(text) {
            if (!ttsToggle?.checked || !text || !window.speechSynthesis) return;
            try {
                window.speechSynthesis.cancel();
                const u = new SpeechSynthesisUtterance(text);
                u.lang = 'ar-SA';
                u.rate = 1;
                window.speechSynthesis.speak(u);
            } catch (err) {}
        }

        function desktopNotify(item) {
            if (!('Notification' in window) || Notification.permission !== 'granted') return;
            try {
                const n = new Notification(item.title || 'وصلة', {
                    body: item.body || '',
                    tag: 'wasla-alert-' + item.id,
                });
                n.onclick = () => {
                    if (item.action_url) window.location.href = item.action_url;
                    n.close();
                };
            } catch (err) {}
        }

        function playBeep() {
            if (!soundUnlocked) return;
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const o = ctx.createOscillator();
                const g = ctx.createGain();
                o.connect(g); g.connect(ctx.destination);
                o.frequency.value = 880;
                g.gain.value = 0.05;
                o.start();
                setTimeout(() => { o.stop(); ctx.close(); }, 180);
            } catch (err) {}
        }

        enableBtn?.addEventListener('click', async () => {
            soundUnlocked = true;
            if ('Notification' in window && Notification.permission === 'default') {
                await Notification.requestPermission();
            }
            // unlock speech + audio with a short phrase
            speak('تم تفعيل تنبيهات وصلة');
            playBeep();
            enableBtn.textContent = 'الصوت مفعّل ✓';
        });

        function render(items) {
            if (!items.length) {
                list.innerHTML = '<div class="text-muted small p-3">لا إشعارات غير مقروءة.</div>';
                return;
            }
            list.innerHTML = items.map((item) => `
                <a class="alert-item ${item.is_read ? '' : 'unread'}" href="${item.action_url || '#'}" data-id="${item.id}">
                    <div class="t">${escapeHtml(item.title || '')}</div>
                    <div class="b">${escapeHtml(item.body || '')}</div>
                    <div class="when">${escapeHtml(item.created_human || '')}</div>
                </a>
            `).join('');

            list.querySelectorAll('.alert-item').forEach((el) => {
                el.addEventListener('click', () => {
                    const id = el.getAttribute('data-id');
                    if (!id) return;
                    fetch(readUrlTpl.replace('__ID__', id), {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    }).catch(() => {});
                });
            });
        }

        function escapeHtml(s) {
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function setBadge(n) {
            if (n > 0) {
                badge.textContent = n > 99 ? '99+' : String(n);
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }
        }

        async function poll() {
            try {
                const res = await fetch(pollUrl + '?after_id=' + lastId, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const data = await res.json();
                setBadge(data.unread_count || 0);
                render(data.unread || []);

                const fresh = (data.fresh || []).slice().sort((a, b) => a.id - b.id);
                if (!bootstrapped) {
                    bootstrapped = true;
                    lastId = data.latest_id || lastId;
                    localStorage.setItem(LS_LAST, String(lastId));
                    return;
                }

                fresh.forEach((item) => {
                    if (item.id <= lastId) return;
                    if (item.speak_text) speak(item.speak_text);
                    desktopNotify(item);
                    playBeep();
                    lastId = Math.max(lastId, item.id);
                });
                if (data.latest_id) {
                    lastId = Math.max(lastId, data.latest_id);
                }
                localStorage.setItem(LS_LAST, String(lastId));
            } catch (err) {}
        }

        markAllBtn?.addEventListener('click', async () => {
            await fetch(readAllUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            poll();
        });

        poll();
        setInterval(poll, 8000);
    })();
    </script>
</body>
</html>
