<?php

return [

    'paths' => [
        'api/*',
        'auth/*', 
        'sanctum/csrf-cookie',
        'kiosk/*',
    ],

    'allowed_methods' => [
        'GET',
        'HEAD',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
    ],

    'allowed_origins' => array_values(array_filter(array_unique([
        // Local development
        'http://localhost:5173',
        'https://localhost:5173',
        'http://127.0.0.1:5173',
        'https://127.0.0.1:5173',
        env('APP_URL', 'http://127.0.0.1:8000'),
        env('FRONTEND_URL', 'http://localhost:5173'),

        // Render Staging & Production frontends
        'https://pupbc-carelink-staging.onrender.com',
        'https://pupbc-carelink-prod.onrender.com',
    ]))),

    'allowed_origins_patterns' => [

        // 192.168.0.0/16
        '#^http://192\.168\.\d{1,3}\.\d{1,3}:5173$#i',

        // 10.0.0.0/8
        '#^http://10\.\d{1,3}\.\d{1,3}\.\d{1,3}:5173$#i',

        // 172.16.0.0 - 172.31.255.255
        '#^http://172\.(1[6-9]|2\d|3[01])\.\d{1,3}\.\d{1,3}:5173$#i',

        // Para sa frontend na gumagamit pa ng port 3000.
        '#^http://192\.168\.\d{1,3}\.\d{1,3}:3000$#i',
        '#^http://10\.\d{1,3}\.\d{1,3}\.\d{1,3}:3000$#i',
        '#^http://172\.(1[6-9]|2\d|3[01])\.\d{1,3}\.\d{1,3}:3000$#i',

    ],

    'allowed_headers' => [
        'Authorization',
        'Content-Type',
        'Accept',
        'Origin',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-Socket-Id',
        'X-Kiosk-Token',
        // Which portal's session cookie a request uses (AuthenticateFromCookie).
        'X-Portal',
        'If-Match',
        'Idempotency-Key',
    ],

    'exposed_headers' => [
        'Authorization',
        'Content-Disposition',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'Retry-After',
    ],

    'max_age' => 86400,

    'supports_credentials' => true,
];