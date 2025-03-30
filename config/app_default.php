<?php

return [
    'debug' => false,

    'App' => [
        'namespace' => 'App',
        'encoding' => 'UTF-8',
        'baseUrl' => '/?url=',
        'defaultName' => 'PHPures 3',
    ],

    'Log' => [
        'debug' => [
            'className' => \Monolog\Handler\StreamHandler::class,
            'formatter' => [
                'className' => \Monolog\Formatter\LineFormatter::class,
                'format' => "%message%\n",
            ],
            'file' => 'php://stderr', /*LOGS . 'debug.log',*/
            'level' => \Monolog\Logger::WARNING,
            'cli' => true,
        ],
        'error' => [
            'className' => \Monolog\Handler\StreamHandler::class,
            'formatter' => [
                'className' => \Monolog\Formatter\LineFormatter::class,
                'format' => "%datetime% > %level_name% > %message% %context% %extra%\n%context.exception%\n",
            ],
            'file' => LOGS . 'error.log',
            'level' => \Monolog\Logger::ERROR,
        ],
    ],

    'PDF' => [
        'engine' => 'TCPDF',
        'TCPDF' => [
            'layout' => 'tcpdf',
        ],
        'WKHTML2PDF' => [
            'layout' => 'wkhtml2pdf',
        ],
    ],
];
