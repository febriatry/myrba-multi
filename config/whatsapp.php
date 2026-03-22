<?php

return [
    'provider' => env('WHATSAPP_PROVIDER', 'ivosight'),
    'ivosight' => [
        'base_url' => env('IVOSIGHT_BASE_URL', ''),
        'api_key' => env('IVOSIGHT_API_KEY', ''),
        'sender_id' => env('IVOSIGHT_SENDER_ID', ''),
        'use_template' => env('IVOSIGHT_USE_TEMPLATE', false),
        'template_endpoints' => array_values(array_filter(array_map('trim', explode(',', env('IVOSIGHT_TEMPLATE_ENDPOINTS', '/api/v1/message-templates,/api/v1/message-templates/all-templates'))))),
        'timeout' => (int) env('IVOSIGHT_TIMEOUT', 30),
        'template_id_billing_reminder' => env('IVOSIGHT_TEMPLATE_ID_BILLING_REMINDER', '69aeb69ecc6bb575760036b4'),
        'template_id_payment_receipt' => env('IVOSIGHT_TEMPLATE_ID_PAYMENT_RECEIPT', '69aebd712118b00e320d7cd5'),
        'template_id_welcome_registration' => env('IVOSIGHT_TEMPLATE_ID_WELCOME_REGISTRATION', ''),
        'template_id_invoice_link' => env('IVOSIGHT_TEMPLATE_ID_INVOICE_LINK', ''),
        'template_id_broadcast' => env('IVOSIGHT_TEMPLATE_ID_BROADCAST', ''),
    ],
];
