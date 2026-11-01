<?php

return [
    'name' => 'BalanceMonitor',
    
    // ServerSpace API
    'serverspace' => [
        'api_key' => env('SERVERSPACE_API_KEY', ''),
    ],
    
    // OpenAI
    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'deposited' => env('OPENAI_DEPOSITED', 5.00),  // Сумма пополнения
        'balance' => env('OPENAI_BALANCE', 0),         // Ручной баланс (если API недоступен)
    ],
    
    // Mango Office
    'mango' => [
        'api_key' => env('MANGO_API_KEY', ''),
        'secret_key' => env('MANGO_SECRET_KEY', ''),
    ],
];
