@extends('layouts.storefront')

@section('title', 'Product Details')

@section('content')
<wasla-product data-product-id="{{ $productId }}"></wasla-product>
@endsection
