<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $seoTitle = trim($__env->yieldContent('title', 'وصلة | توصيل شي إن SHEIN لسوريا — دمشق والمحافظات'));
        $seoDescription = trim($__env->yieldContent(
            'meta_description',
            'وصلة Wasla: تسوق شي إن SHEIN والمتاجر العالمية مع توصيل إلى سوريا — دمشق وحلب وحمص واللاذقية وطرطوس وباقي المحافظات. طلب سهل، أسعار واضحة، وتتبع للشحنة.'
        ));
        $seoKeywords = trim($__env->yieldContent(
            'meta_keywords',
            'وصلة, Wasla, شي إن سوريا, SHEIN سوريا, توصيل شي إن دمشق, شي ان دمشق, تسوق أونلاين سوريا, توصيل للمحافظات, حلب, حمص, اللاذقية, طرطوس, شراء من شي إن, وسيط شي إن'
        ));
        $canonical = trim($__env->yieldContent('canonical', url()->current()));
        $ogImage = trim($__env->yieldContent('og_image', url('/brand/wasla-id-horizontal.png')));
        $siteName = 'وصلة Wasla';
    @endphp

    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="keywords" content="{{ $seoKeywords }}">
    <meta name="author" content="وصلة Wasla">
    <meta name="robots" content="@yield('robots', 'index,follow,max-image-preview:large')">
    <meta name="geo.region" content="SY">
    <meta name="geo.placename" content="Damascus, Syria">
    <meta name="language" content="Arabic">
    <link rel="canonical" href="{{ $canonical }}">

    <meta property="og:locale" content="ar_SY">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <link rel="icon" href="{{ url('/brand/wasla-id-mark.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ url('/brand/wasla-id-mark.png') }}">

    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => url('/').'#organization',
                'name' => 'وصلة Wasla',
                'alternateName' => ['Wasla', 'وصلة ستور', 'Wasla Store'],
                'url' => url('/'),
                'logo' => url('/brand/wasla-id-mark.png'),
                'image' => url('/brand/wasla-id-horizontal.png'),
                'description' => 'منصة تسوق سورية لتوصيل منتجات شي إن SHEIN والمتاجر العالمية إلى دمشق وباقي المحافظات.',
                'areaServed' => [
                    ['@type' => 'Country', 'name' => 'Syria'],
                    ['@type' => 'City', 'name' => 'Damascus'],
                    ['@type' => 'City', 'name' => 'Aleppo'],
                    ['@type' => 'City', 'name' => 'Homs'],
                    ['@type' => 'City', 'name' => 'Latakia'],
                    ['@type' => 'City', 'name' => 'Tartus'],
                    ['@type' => 'City', 'name' => 'Hama'],
                    ['@type' => 'City', 'name' => 'Daraa'],
                    ['@type' => 'City', 'name' => 'Sweida'],
                ],
                'knowsAbout' => ['SHEIN', 'شي إن', 'توصيل دولي لسوريا', 'تسوق أونلاين'],
            ],
            [
                '@type' => 'WebSite',
                '@id' => url('/').'#website',
                'url' => url('/'),
                'name' => 'وصلة Wasla',
                'inLanguage' => 'ar',
                'publisher' => ['@id' => url('/').'#organization'],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => url('/shop').'?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            [
                '@type' => 'OnlineStore',
                '@id' => url('/').'#store',
                'name' => 'وصلة — توصيل شي إن لسوريا',
                'url' => url('/'),
                'image' => url('/brand/wasla-id-horizontal.png'),
                'description' => 'اطلبي من شي إن SHEIN والمتاجر العالمية مع توصيل إلى سوريا: دمشق، حلب، حمص، اللاذقية، طرطوس وباقي المحافظات.',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => 'Damascus',
                    'addressCountry' => 'SY',
                ],
                'areaServed' => 'SY',
                'currenciesAccepted' => 'SYP',
                'parentOrganization' => ['@id' => url('/').'#organization'],
            ],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
    @stack('jsonld')

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
    <noscript>
        <p>وصلة Wasla — تسوق شي إن SHEIN مع توصيل إلى سوريا (دمشق والمحافظات).</p>
    </noscript>
</body>
</html>
