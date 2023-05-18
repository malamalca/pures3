<?php

return [
    'debug' => false,

    'App' => [
        'namespace' => 'App',
        'encoding' => 'UTF-8',
        'baseUrl' => '/www/pures3',
        'defaultName' => 'Pures 3',
    ],

    'Log' => [
        'debug' => [
            'className' => 'StreamHandler',
            'file' => 'php://stderr', //LOGS . 'debug.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        'info' => [
            'className' => 'StreamHandler',
            'file' => 'php://stderr', //LOGS . 'debug.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
    ],
];
