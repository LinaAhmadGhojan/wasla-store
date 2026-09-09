<?php

return [
    'methods' => [
        'standard' => [
            'label' => 'شحن عادي',
            'fee' => (float) env('SHIPPING_STANDARD_FEE', 0),
            'eta' => '2–5 أيام',
        ],
        'express' => [
            'label' => 'توصيل سريع',
            'fee' => (float) env('SHIPPING_EXPRESS_FEE', 25),
            'eta' => '24–48 ساعة',
        ],
    ],

    'gift_wrapping_fee' => (float) env('GIFT_WRAPPING_FEE', 15),

    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 3),

    /** 1 نقطة = 1 ل.س من رصيد المتجر */
    'points_to_syp' => 1,
];
