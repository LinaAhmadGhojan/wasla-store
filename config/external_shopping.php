<?php

return [
    /*
    |--------------------------------------------------------------------------
    | External catalog browse driver
    |--------------------------------------------------------------------------
    |
    | mock      = Demo catalog inside Wasla (works offline, for UX + development)
    | searchapi = SearchAPI.io paid product search (set SEARCHAPI_KEY)
    | otapi     = OTAPI / RapidAPI (set OTAPI_KEY) — adapter stub ready
    |
    */
    'catalog_driver' => env('EXTERNAL_CATALOG_DRIVER', 'mock'),

    'searchapi' => [
        'api_key' => env('SEARCHAPI_KEY'),
        'base_url' => env('SEARCHAPI_BASE_URL', 'https://www.searchapi.io/api/v1/search'),
    ],

    'otapi' => [
        'api_key' => env('OTAPI_KEY'),
        'host' => env('OTAPI_HOST', 'otapi-shein.p.rapidapi.com'),
    ],

    'default_country' => 'AE',
    'default_currency' => 'AED',
];
