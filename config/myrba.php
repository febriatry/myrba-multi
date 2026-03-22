<?php

return [
    'api_key' => env('MYRBA_API_KEY', env('API_KEY')),
    'api_keys' => array_values(array_filter([
        env('MYRBA_API_KEY', env('API_KEY')),
        env('MYRBA_ADMIN_API_KEY'),
    ], function ($v) {
        return is_string($v) && trim($v) !== '';
    })),
];
