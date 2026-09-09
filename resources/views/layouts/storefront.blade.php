<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Wasla')) &mdash; Wasla Store</title>
    <script>
        window.WASLA_AED_TO_SYP = @json(app(\App\Services\CurrencyService::class)->currentAedToSypRate());
        window.WASLA_PRODUCT_FEE_AED = @json(app(\App\Services\CurrencyService::class)->productFeeAed());
        window.WASLA_ACCESSORY_FEE_AED = @json(app(\App\Services\CurrencyService::class)->accessoryFeeAed());
        window.WASLA_PAYMENT_METHODS = @json(config('payments.methods'));
        window.WASLA_PAYMENT_INSTRUCTIONS = @json(config('payments.instructions'));
        window.WASLA_PAYMENT_ICONS = @json(config('payments.icons'));
        window.WASLA_PAYMENT_BRAND = @json(config('payments.brand'));
        window.WASLA_PAYMENT_ACCOUNTS = @json(config('payments.accounts'));
        window.WASLA_PAYMENT_RULES = @json(config('payments.rules'));
        window.WASLA_GOOGLE_CLIENT_ID = @json(config('auth_social.google.client_id'));
        window.WASLA_AUTH_PROVIDERS = @json([
            'google' => (bool) config('auth_social.google.enabled'),
            'apple' => (bool) config('auth_social.apple.enabled'),
        ]);
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface text-slate-900">
    <div id="app">
        @yield('content')
    </div>
</body>
</html>
