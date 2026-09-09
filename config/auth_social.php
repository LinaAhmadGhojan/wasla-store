<?php

return [
    /*
    | Google / Apple معلّقان حالياً — التسجيل بالإيميل + رمز تأكيد فقط.
    | لتفعيله لاحقاً: SOCIAL_LOGIN_ENABLED=true مع GOOGLE_CLIENT_ID / APPLE_CLIENT_ID
    */
    'social_enabled' => (bool) env('SOCIAL_LOGIN_ENABLED', false),

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'enabled' => (bool) env('SOCIAL_LOGIN_ENABLED', false) && (bool) env('GOOGLE_CLIENT_ID'),
    ],
    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'enabled' => (bool) env('SOCIAL_LOGIN_ENABLED', false) && (bool) env('APPLE_CLIENT_ID'),
        'allow_dev_bypass' => (bool) env('APPLE_AUTH_DEV_BYPASS', false),
    ],
    'otp' => [
        'ttl_minutes' => (int) env('AUTH_OTP_TTL', 10),
        'expose_in_response' => env('AUTH_OTP_EXPOSE', null) !== null
            ? (bool) env('AUTH_OTP_EXPOSE')
            : env('APP_ENV') === 'local',
    ],

    /** يجب تأكيد الإيميل برمز قبل تفعيل الحساب */
    'require_email_verification' => (bool) env('REQUIRE_EMAIL_VERIFICATION', true),
];
