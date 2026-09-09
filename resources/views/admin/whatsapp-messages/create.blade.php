@php
    $action = $message->exists
        ? route('admin.whatsapp-messages.update', $message)
        : route('admin.whatsapp-messages.store');
    $method = $message->exists ? 'put' : 'post';
    $title = $message->exists ? 'تعديل رسالة' : 'رسالة جديدة';
@endphp

@extends('admin.layout')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">{{ $title }}</h1>

    @include('admin.whatsapp-messages._form', [
        'action' => $action,
        'method' => $method,
        'message' => $message,
        'categories' => $categories,
    ])
</div>
@endsection
