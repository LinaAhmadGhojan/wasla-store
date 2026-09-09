@extends('layouts.storefront')

@section('title', 'منتج خارجي')

@section('content')
<wasla-browse-product
    data-platform="{{ $platform }}"
    data-product-id="{{ $productId }}"
></wasla-browse-product>
@endsection
