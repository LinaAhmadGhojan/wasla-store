<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging
    |--------------------------------------------------------------------------
    | 1) أنشئ مشروع Firebase واربط تطبيق Android/iOS
    | 2) Project settings → Service accounts → Generate new private key
    | 3) ضع الملف في storage/app/firebase-credentials.json (أو مسار آخر)
    | 4) فعّل FIREBASE_ENABLED=true وأضف FIREBASE_PROJECT_ID
    */
    'enabled' => (bool) env('FIREBASE_ENABLED', false),

    'project_id' => env('FIREBASE_PROJECT_ID'),

    'credentials' => env(
        'FIREBASE_CREDENTIALS',
        storage_path('app/firebase-credentials.json')
    ),

    'android_channel' => env('FIREBASE_ANDROID_CHANNEL', 'wasla_orders'),
];
