@php
    $action = route('admin.whatsapp-messages.update', $message);
    $method = 'put';
    $title = 'تعديل رسالة';
@endphp

@extends('admin.layout')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">{{ $title }}: {{ $message->title }}</h1>

    @include('admin.whatsapp-messages._form', [
        'action' => $action,
        'method' => $method,
        'message' => $message,
        'categories' => $categories,
    ])
</div>
@endsection
