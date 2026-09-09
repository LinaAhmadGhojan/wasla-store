@extends('layouts.storefront')

@section('title', 'تتبع الطلب')

@section('content')
    <wasla-order-track data-order-id="{{ $orderId }}"></wasla-order-track>
@endsection
