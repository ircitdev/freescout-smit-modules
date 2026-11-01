<?php

return [
    'name' => 'VoiceMail',

    // Уникальный ID канала для голосовой почты
    'channel_id' => 200,

    // Отображаемое имя канала (номер телефона)
    'channel_name' => '55-35-59',

    // ID mailbox для голосовой почты
    // Можно узнать в базе данных: SELECT id, name FROM mailboxes;
    'voicemail_mailbox_id' => env('VOICEMAIL_MAILBOX_ID', 7),

    // Иконка для канала (Bootstrap Glyphicon)
    'channel_icon' => '<i class="glyphicon glyphicon-earphone"></i>',

    // SMS уведомления через Mango Office API
    'send_connection_sms' => env('VOICEMAIL_SEND_CONNECTION_SMS', false),

    // Опции модуля
    'options' => [
        'enabled' => [
            'default' => true,
        ],
        'auto_assign_channel' => [
            'default' => true,
            'description' => 'Автоматически назначать channel=200 для тикетов из голосовой почты'
        ],
    ],
];
