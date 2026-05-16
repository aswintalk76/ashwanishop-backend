<?php

$frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_unique(array_filter([
        $frontendUrl,
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'https://ashwanishop.helloashwani.site',
        'https://www.ashwanishop.helloashwani.site',
    ]))),

  // Allow any helloashwani.site subdomain (frontend + staging)
    'allowed_origins_patterns' => [
        '#^https?://([a-z0-9-]+\\.)*helloashwani\\.site$#',
        '#^https?://localhost(:\\d+)?$#',
        '#^https?://127\\.0\\.0\\.1(:\\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    // Bearer tokens only — must be false for simple cross-origin API calls
    'supports_credentials' => false,

];
