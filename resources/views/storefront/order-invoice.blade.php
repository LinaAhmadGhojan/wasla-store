@extends('layouts.storefront')

@section('title', 'فاتورة الطلب')

@section('content')
    <wasla-order-invoice data-order-id="{{ $orderId }}"></wasla-order-invoice>
@endsection
