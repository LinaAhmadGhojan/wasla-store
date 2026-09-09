<?php

return [
    /*
    |--------------------------------------------------------------------------
    | طرق الدفع المتاحة حالياً (سوريا)
    | الترتيب = الظهور في الـ checkout (الأكثر استخداماً أولاً)
    |--------------------------------------------------------------------------
    | كاش عند الاستلام معلّق مؤقتاً — لا مبلغ جزئي إجباري حالياً.
    */
    'methods' => [
        'sham_cash' => 'شام كاش',
        'al_haram' => 'الهرم',
        'fouad' => 'الفؤاد',
        'store_credit' => 'رصيد المتجر',
    ],

    /** معلّق — يُعاد تفعيله لاحقاً دون مبلغ جزئي إجباري */
    'paused' => [
        'cash_on_delivery' => 'كاش عند الاستلام',
    ],

    'icons' => [
        'sham_cash' => '/brand/payments/sham-cash.png',
        'al_haram' => '/brand/payments/al-haram.png',
        'fouad' => '/brand/payments/fouad.png',
        'store_credit' => '/brand/payments/store-credit.svg',
        'cash_on_delivery' => '/brand/payments/cod.svg',
    ],

    'brand' => [
        'sham_cash' => ['color' => '#6B2D8B', 'short' => 'شام'],
        'al_haram' => ['color' => '#1B7A4E', 'short' => 'هرم'],
        'fouad' => ['color' => '#1A4F8B', 'short' => 'فؤاد'],
        'store_credit' => ['color' => '#1c7282', 'short' => 'رصيد'],
        'cash_on_delivery' => ['color' => '#C27803', 'short' => 'كاش'],
    ],

    'instructions' => [
        'sham_cash' => 'حوّلي {amount} عبر شام كاش إلى حساب وصلة، ثم أدخلي كود العملية من التطبيق (إلزامي). الطلب يبقى بانتظار تأكيد الدفع من وصلة.',
        'al_haram' => 'حوّلي {amount} عبر الهرم للحوالات إلى وصلة، ثم ارفعي صورة الوصل أو أدخلي كود التحويل (واحد منهم إلزامي). الطلب يبقى بانتظار تأكيد الدفع من وصلة.',
        'fouad' => 'حوّلي {amount} عبر الفؤاد للحوالات إلى وصلة، ثم ارفعي صورة الوصل أو أدخلي كود التحويل (واحد منهم إلزامي). الطلب يبقى بانتظار تأكيد الدفع من وصلة.',
        'store_credit' => 'سيُخصم المبلغ من رصيد حسابك في وصلة فوراً ويبدأ التجهيز.',
        'cash_on_delivery' => 'الدفع كامل عند التسليم (كاش). فريق وصلة يرتّب معك الموعد. ما في دفع مسبق.',
    ],

    'accounts' => [
        'sham_cash' => env('PAY_SHAM_CASH_ACCOUNT', 'حساب شام كاش لوصلة — يُحدَّث من الإدارة'),
        'al_haram' => env('PAY_AL_HARAM_ACCOUNT', 'اسم المستفيد: وصلة — راجع فرع الهرم الأقرب'),
        'fouad' => env('PAY_FOUAD_ACCOUNT', 'اسم المستفيد: وصلة — راجع فرع الفؤاد الأقرب'),
        'store_credit' => 'يُخصم من رصيد حسابك في وصلة',
        'cash_on_delivery' => 'الدفع الكامل عند باب المنزل / نقطة الاستلام',
    ],

    'rules' => [
        'sham_cash' => [
            'require' => ['transfer_code'],
            'admin_confirm' => true,
        ],
        'al_haram' => [
            'require_one_of' => ['receipt', 'transfer_code'],
            'admin_confirm' => true,
        ],
        'fouad' => [
            'require_one_of' => ['receipt', 'transfer_code'],
            'admin_confirm' => true,
        ],
        'store_credit' => [
            'require' => [],
            'admin_confirm' => false,
            'start_preparing' => true,
            'auto_debit' => true,
        ],
        'cash_on_delivery' => [
            'require' => [],
            'admin_confirm' => false,
            'start_preparing' => true,
        ],
    ],

    'no_receipt' => ['cash_on_delivery', 'store_credit', 'sham_cash'],

    'auto_confirm' => ['cash_on_delivery', 'store_credit'],
];
