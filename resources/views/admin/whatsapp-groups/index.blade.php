@extends('admin.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1">WhatsApp Groups</h1>
            <p class="text-muted mb-0">ربط مجموعة واتساب، رقم الأدمن، وقالب الرسالة للنشر.</p>
        </div>
        <a href="{{ route('admin.whatsapp-groups.create') }}" class="btn btn-primary">Add Group</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="alert alert-info">
        <strong>إرسال تلقائي:</strong> للرقم أو للمجموعة — اختاري الوجهة وقت النشر.
        للمجموعة: اربطي <strong>Group Chat ID</strong> من Edit (زر «جلب المجموعات»).
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Invite link</th>
                        <th>Chat ID</th>
                        <th>Admin phone</th>
                        <th>Admin user</th>
                        <th>Default</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $group)
                        <tr>
                            <td>{{ $group->name }}</td>
                            <td>
                                @if($group->invite_link)
                                    <a href="{{ $group->invite_link }}" target="_blank" rel="noopener">Open</a>
                                @else — @endif
                            </td>
                            <td>{{ $group->whatsapp_chat_id ? '✓' : '—' }}</td>
                            <td>{{ $group->admin_phone ? '+' . $group->admin_phone : '—' }}</td>
                            <td>{{ $group->adminUser?->name ?? '—' }}</td>
                            <td>@if($group->is_default)<span class="badge bg-primary">Default</span>@endif</td>
                            <td><span class="badge bg-{{ $group->is_active ? 'success' : 'secondary' }}">{{ $group->is_active ? 'Active' : 'Off' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.whatsapp-groups.edit', $group) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('admin.whatsapp-groups.destroy', $group) }}" method="post" class="d-inline-block">
                                    @csrf @method('delete')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4">No groups yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
