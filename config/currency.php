<?php

return [
    'procurement' => env('CURRENCY_PROCUREMENT', 'AED'),
    'customer' => env('CURRENCY_CUSTOMER', 'SYP'),

    /** Fallback when no rate is stored yet (1 AED = X SYP). */
    'default_aed_to_syp' => (float) env('DEFAULT_AED_TO_SYP', 14500),

    /**
     * Customer SYP = (AED price × exchange rate) + fee.
     * Product (not accessory) → +75 ل.س after conversion.
     * Accessory → +50 ل.س after conversion.
     * Example: 20 د.إ × 38 + 75.
     * Editable from Admin → سعر الصرف.
     */
    'product_fee_aed' => (float) env('WASLA_PRODUCT_FEE_AED', 75),
    'accessory_fee_aed' => (float) env('WASLA_ACCESSORY_FEE_AED', 50),

    'labels' => [
        'AED' => 'د.إ',
        'SYP' => 'ل.س',
    ],
];
