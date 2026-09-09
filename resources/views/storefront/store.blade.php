@extends('layouts.storefront')

@section('title', 'المتجر — وصلة')

@section('content')
<wasla-store data-store-id="{{ $storeId }}"></wasla-store>
@endsection
