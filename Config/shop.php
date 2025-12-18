return [
    'enabled' => true,
    'currency' => 'FC',
    'currency_position' => 'after',
    
    'discounts' => [
        'enabled' => true,
        'apply_to_forever' => true,
    ],
    
    'notifications' => [
        'email' => [
            'enabled' => true,
        ],
        'telegram' => [
            'enabled' => false,
            'bot_token' => env('TELEGRAM_BOT_TOKEN'),
            'chat_id' => env('TELEGRAM_CHAT_ID'),
        ],
    ],
    
    'drivers' => [
        'vip' => [
            'enabled' => true,
            'handler' => \App\Modules\Shop\Drivers\VipDriver::class,
        ],
        // ...
    ],
];