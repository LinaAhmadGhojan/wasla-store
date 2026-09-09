<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default message template (placeholders)
    |--------------------------------------------------------------------------
    | {name} {catalog} {description} {price} {price_syp} {sale_price} {currency} {url} {image} {store}
    */
    'default_template' => <<<'TXT'
🛍️ *{name}*

{catalog}
TXT,

    /**
     * Include the storefront product URL in WhatsApp messages.
     * Off for now; set WHATSAPP_INCLUDE_PRODUCT_URL=true later, and add {url} to the template.
     */
    'include_product_url' => (bool) env('WHATSAPP_INCLUDE_PRODUCT_URL', false),

    'currency' => 'AED',

    /*
    |--------------------------------------------------------------------------
    | Default recipient phone (E.164 digits, no +)
    |--------------------------------------------------------------------------
    | Product messages open WhatsApp chat with this number pre-filled.
    */
    'default_phone' => env('WHATSAPP_DEFAULT_PHONE', '963958443182'),

    /*
    | Inquiry numbers shown on product WhatsApp messages (first is primary).
    */
    'contact_phones' => array_values(array_filter([
        env('WHATSAPP_CONTACT_PHONE', '963939052948'),
        env('WHATSAPP_DEFAULT_PHONE', '963958443182'),
    ])),

    /*
    |--------------------------------------------------------------------------
    | Auto-send gateway (whatsapp-web.js microservice)
    |--------------------------------------------------------------------------
    | Run: cd whatsapp-gateway && npm install && npm start
    | Scan QR once at /admin/whatsapp/connection
    */
    'gateway_url' => env('WHATSAPP_GATEWAY_URL', 'http://127.0.0.1:3001'),
    'gateway_token' => env('WHATSAPP_GATEWAY_TOKEN', 'wasla-local-token'),

    /** Max products per bulk send (keeps request under PHP time limit). */
    'bulk_max_products' => (int) env('WHATSAPP_BULK_MAX', 8),

    /** Delay between bulk messages in milliseconds. */
    'bulk_delay_ms' => (int) env('WHATSAPP_BULK_DELAY_MS', 600),

    /** Default send target on product share page: phone | group */
    'default_send_target' => env('WHATSAPP_DEFAULT_SEND_TARGET', 'phone'),
];
