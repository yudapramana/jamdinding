<?php

return [

    'default' => env('LOG_CHANNEL', 'errorlog'),

    'channels' => [

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => Monolog\Handler\NullHandler::class,
        ],
    ],
];
