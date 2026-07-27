<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default operational settings
    |--------------------------------------------------------------------------
    |
    | Fallback values used whenever a company (or branch override) has not
    | defined its own value for a given key. See App\Services\SettingsService.
    |
    */
    'defaults' => [
        'allow_negative_stock' => false,
        'require_open_register' => true,
        'prices_include_tax' => true,
        'rounding' => 'none',
        'expiry_alert_days' => 30,
        'low_stock_default_threshold' => 5,
        'ticket_footer_message' => 'Gracias por su compra',
        'max_discount_percentage_without_authorization' => 10,
    ],
];
