<?php

return [
    'local_errand' => [
        'base_fee_syp' => 20000,
        'per_item_syp' => 2500,
        'category_fees' => [
            'grocery' => 20000,
            'pharmacy' => 25000,
            'produce' => 18000,
            'household' => 22000,
            'other' => 20000,
        ],
        'urgent_multiplier' => 1.35,
    ],
    'same_governorate_fee_syp' => 25000,
    'default_inter_governorate_fee_syp' => 95000,
    'parcel_sizes' => [
        'small' => 35000,
        'medium' => 55000,
        'large' => 90000,
        'unknown' => 60000,
    ],
    'carriers' => [
        [
            'key' => 'wasla',
            'label' => 'وصلة — توصيل محلي',
            'available' => true,
            'fee_markup_syp' => 0,
            'badge' => 'سريع',
        ],
        [
            'key' => 'qadmous',
            'label' => 'شحن قدموس',
            'available' => true,
            'fee_markup_syp' => 5000,
            'badge' => 'بين المحافظات',
        ],
        [
            'key' => 'mufti',
            'label' => 'شحن المفتي',
            'available' => true,
            'fee_markup_syp' => 4000,
            'badge' => 'بين المحافظات',
        ],
        [
            'key' => 'wasla_intergov',
            'label' => 'وصلة بين المحافظات',
            'available' => false,
            'fee_markup_syp' => 0,
            'badge' => 'قريباً',
        ],
    ],
    'governorate_routes' => [
        ['from' => 'دمشق', 'to' => 'حلب', 'fee_syp' => 85000, 'carrier' => 'qadmous'],
        ['from' => 'دمشق', 'to' => 'اللاذقية', 'fee_syp' => 75000, 'carrier' => 'qadmous'],
        ['from' => 'دمشق', 'to' => 'حمص', 'fee_syp' => 55000, 'carrier' => 'mufti'],
        ['from' => 'حلب', 'to' => 'دمشق', 'fee_syp' => 85000, 'carrier' => 'qadmous'],
    ],
];
