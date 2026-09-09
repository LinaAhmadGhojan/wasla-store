@include('admin.whatsapp-groups._form', [
    'action' => route('admin.whatsapp-groups.update', $group),
    'method' => 'put',
    'title' => 'Edit WhatsApp Group',
])
