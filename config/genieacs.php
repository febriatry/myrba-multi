<?php

return [
    'nbi_base_url' => env('GENIEACS_NBI_BASE_URL', 'https://acs.myrba.net/nbi'),
    'username' => env('GENIEACS_NBI_USERNAME', ''),
    'password' => env('GENIEACS_NBI_PASSWORD', ''),
    'timeout' => (int) env('GENIEACS_NBI_TIMEOUT', 15),
    'verify_tls' => (bool) env('GENIEACS_NBI_VERIFY_TLS', true),
];
