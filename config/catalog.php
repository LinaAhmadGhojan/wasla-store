<?php

return [
    /*
    | فلاتر عامة دائماً ظاهرة
    */
    'common_filters' => [
        'price', 'brand', 'store', 'rating', 'discount', 'availability',
        'color', 'size', 'gender', 'shipping', 'fast_delivery',
    ],

    /*
    | فلاتر حسب نوع التصنيف (slug يحتوي الكلمة أو الاسم)
    */
    'category_profiles' => [
        'clothing' => [
            'match' => ['clothing', 'clothes', 'apparel', 'fashion', 'ملابس', 'فساتين', 'تيشيرت', 'قمصان', 'بنطلون'],
            'filters' => ['size', 'color', 'material', 'fit', 'pattern', 'length', 'type', 'occasion'],
        ],
        'shoes' => [
            'match' => ['shoe', 'shoes', 'sneaker', 'أحذية', 'حذاء', 'كعب'],
            'filters' => ['size', 'color', 'material', 'heel_height', 'style'],
        ],
        'beauty' => [
            'match' => ['beauty', 'makeup', 'skincare', 'جمال', 'مكياج', 'عناية'],
            'filters' => ['type', 'brand', 'material'],
        ],
        'bags' => [
            'match' => ['bag', 'bags', 'حقيبة', 'حقائب'],
            'filters' => ['color', 'material', 'style', 'type'],
        ],
        'default' => [
            'match' => [],
            'filters' => ['color', 'size', 'material', 'type', 'occasion', 'age'],
        ],
    ],

    'filter_labels' => [
        'price' => 'السعر',
        'color' => 'اللون',
        'size' => 'المقاس',
        'brand' => 'البراند',
        'store' => 'المتجر',
        'rating' => 'التقييم',
        'discount' => 'الخصم',
        'availability' => 'التوفر',
        'material' => 'المادة',
        'type' => 'النوع',
        'occasion' => 'المناسبة',
        'age' => 'العمر',
        'gender' => 'الجنس',
        'shipping' => 'الشحن',
        'fast_delivery' => 'توصيل سريع',
        'fit' => ' القصة / Fit',
        'pattern' => 'النقشة',
        'length' => 'الطول',
        'heel_height' => 'ارتفاع الكعب',
        'style' => 'الأسلوب',
    ],

    'sorts' => [
        'newest' => 'الأحدث',
        'bestsellers' => 'الأكثر مبيعاً',
        'most_viewed' => 'الأكثر مشاهدة',
        'top_rated' => 'الأعلى تقييماً',
        'price_asc' => 'السعر: من الأقل',
        'price_desc' => 'السعر: من الأعلى',
        'discount' => 'أكبر خصم',
        'trending' => 'الرائج',
    ],

    'suggestion_prefixes' => [
        'black' => ['Black Dress', 'Black Maxi Dress', 'Black Evening Dress', 'Black Summer Dress'],
        'white' => ['White Dress', 'White Shirt', 'White Sneakers'],
        'red' => ['Red Dress', 'Red Heels', 'Red Bag'],
        'اسود' => ['فستان أسود', 'فستان أسود طويل', 'فستان سهرة أسود'],
        'ابيض' => ['فستان أبيض', 'قميص أبيض'],
    ],
];
