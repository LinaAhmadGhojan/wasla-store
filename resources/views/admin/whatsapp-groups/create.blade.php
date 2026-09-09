@include('admin.whatsapp-groups._form', [
    'action' => route('admin.whatsapp-groups.store'),
    'method' => 'post',
    'title' => 'Add WhatsApp Group',
])
