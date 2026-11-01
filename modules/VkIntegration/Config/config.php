<?php

return [
    'name' => 'VkIntegration',

    /*
    |--------------------------------------------------------------------------
    | VK Bridge Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the VK Python bridge service
    |
    */
    'bridge_url' => env('VK_BRIDGE_URL', 'http://localhost:8901'),

    /*
    |--------------------------------------------------------------------------
    | VK Channel ID
    |--------------------------------------------------------------------------
    |
    | Custom channel ID for VK conversations in FreeScout
    |
    */
    'channel_id' => 101,
    'channel_icon' => '<i class="glyphicon glyphicon-bold"></i>',

    /*
    |--------------------------------------------------------------------------
    | Default Mailbox
    |--------------------------------------------------------------------------
    |
    | Default mailbox ID for VK conversations
    |
    */
    'default_mailbox' => env('VK_DEFAULT_MAILBOX', 1),
];
