@extends('layouts.storefront')

@section('title', 'طبق | طلباتي ')
@section('meta_description', 'تفاصيل الطبق من طلباتي  — توصيل قريب في دمشق.')

@section('content')
<wasla-express-item data-item-id="{{ $itemId }}"></wasla-express-item>
@endsection
