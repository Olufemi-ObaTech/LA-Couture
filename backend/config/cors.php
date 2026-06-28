<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'https://la-couture.netlify.app'),
        'https://la-couture.netlify.app',
        'https://lacouture.netlify.app',
        'http://localhost:5500',
        'http://127.0.0.1:5500',
        'http://localhost:5501',
        'http://127.0.0.1:5501',
        'http://localhost:8080',
        'http://127.0.0.1:8080',
        'http://localhost',
        'http://127.0.0.1',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'X-XSRF-TOKEN'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,
];
