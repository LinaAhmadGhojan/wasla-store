@extends('admin.layout')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">{{ $title }}</h1>

    <form action="{{ $action }}" method="post" class="card shadow-sm border-0">
        @csrf
        @if($method !== 'post') @method($method) @endif
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Group name *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $group->name) }}" required placeholder="الادارة لوصلة">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Invite link</label>
                    <input type="url" name="invite_link" class="form-control" value="{{ old('invite_link', $group->invite_link) }}" placeholder="https://chat.whatsapp.com/...">
                    <div class="form-text">يُستخدم لـ «ربط تلقائي» حتى لو ما ظهرت المجموعة بقائمة «جلب المجموعات».</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Group Chat ID (للإرسال التلقائي)</label>
                    <div class="input-group flex-wrap gap-1">
                        <input type="text" name="whatsapp_chat_id" id="whatsapp_chat_id" class="form-control font-monospace"
                            value="{{ old('whatsapp_chat_id', $group->whatsapp_chat_id) }}"
                            placeholder="120363xxxxxxxx@g.us">
                        <button type="button" class="btn btn-outline-secondary" id="loadGroupsBtn">جلب المجموعات</button>
                        @if($group->exists)
                            <form action="{{ route('admin.whatsapp-groups.sync-chat-id', $group) }}" method="post" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary">ربط تلقائي</button>
                            </form>
                        @endif
                    </div>
                    <select id="groupPicker" class="form-select mt-2 d-none">
                        <option value="">— اختاري مجموعة —</option>
                    </select>
                    <div class="form-text">لازم الحساب المربوط بـ <a href="{{ route('admin.whatsapp.connection') }}">WhatsApp Connection</a> يكون عضو بالمجموعة.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Admin WhatsApp phone</label>
                    <input type="text" name="admin_phone" class="form-control" value="{{ old('admin_phone', $group->admin_phone ? '+' . $group->admin_phone : '') }}" placeholder="+963958443182">
                    <div class="form-text">للمراسلة المباشرة مع الأدمن (اختياري).</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Linked admin user</label>
                    <select name="admin_user_id" class="form-select">
                        <option value="">—</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('admin_user_id', $group->admin_user_id) == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Message template</label>
                    <textarea name="message_template" rows="10" class="form-control font-monospace" placeholder="Leave empty for default">{{ old('message_template', $group->message_template) }}</textarea>
                    <div class="form-text">
                        Placeholders: <code>{name}</code> <code>{catalog}</code> (الألوان + المقاسات + السعر بالليرة) <code>{description}</code> <code>{price}</code> <code>{price_syp}</code> <code>{sale_price}</code> <code>{currency}</code> <code>{url}</code> <code>{image}</code> <code>{store}</code> <code>{group_name}</code> <code>{sale_line}</code>
                        <br>رابط المنتج <code>{url}</code> مطفي حالياً. لتفعيله لاحقاً: ضيفيه للقالب وحطي <code>WHATSAPP_INCLUDE_PRODUCT_URL=true</code> بملف <code>.env</code>.
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default" @checked(old('is_default', $group->is_default))>
                        <label class="form-check-label" for="is_default">Default group</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $group->is_active ?? true))>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('admin.whatsapp-groups.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>

    @if(empty($group->message_template))
        <div class="card mt-4 border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6">Default template preview</h2>
                <pre class="mb-0 small">{{ $defaultTemplate }}</pre>
            </div>
        </div>
    @endif
</div>

<script>
document.getElementById('loadGroupsBtn')?.addEventListener('click', async () => {
    const picker = document.getElementById('groupPicker');
    const input = document.getElementById('whatsapp_chat_id');
    picker.classList.remove('d-none');
    picker.innerHTML = '<option value="">جاري التحميل…</option>';

    try {
        const res = await fetch(@json(route('admin.whatsapp.groups')));
        const data = await res.json();
        if (!data.ready) {
            alert('واتساب غير متصل. اربطيه من صفحة Connection أولاً.');
            return;
        }
        picker.innerHTML = '<option value="">— اختاري مجموعة —</option>';
        (data.groups || []).forEach((g) => {
            const opt = document.createElement('option');
            opt.value = g.id;
            opt.textContent = g.name;
            picker.appendChild(opt);
        });
        if (!(data.groups || []).length) {
            alert('ما لقينا مجموعات. تأكدي إنو الحساب المربوط عضو بمجموعات.');
        }
    } catch (e) {
        alert('فشل جلب المجموعات.');
    }

    picker.onchange = () => {
        if (picker.value) input.value = picker.value;
    };
});
</script>
@endsection
