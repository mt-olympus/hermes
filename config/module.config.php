<?php

return [
    'service_manager' => [
        'aliases' => [
            'hermes' => 'Hermes\Api\Client',
        ],
        'factories' => [
            Hermes\Api\Client::class => Hermes\Api\ClientFactory::class,
        ],
    ],
    'hermes' => [
        'uri' => 'http://localhost:8000',
        'depth' => 0,
        'headers' => array(
            'Accept' => 'application/hal+json',
            'Content-Type' => 'application/json',
        ),
        'http_client' => [
            'options' => [],
        ],
    ],
];
