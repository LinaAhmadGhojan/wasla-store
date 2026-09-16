@extends('layouts.storefront')

@section('title', 'متجر | طلباتي ')
@section('meta_description', 'قائمة متجر طلباتي  — أطباق وتوصيل قريب في دمشق.')

@section('content')
<wasla-express-store data-store-id="{{ $storeId }}"></wasla-express-store>
@endsection
