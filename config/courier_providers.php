<?php

return [
    'bluedart' => [
        'api_base_url' => env('BLUEDART_API_BASE_URL', 'https://apigateway.bluedart.com'),
        'jwt_token' => env('BLUEDART_JWT_TOKEN'),
        'login_id' => env('BLUEDART_LOGIN_ID'),
        'licence_key' => env('BLUEDART_LICENCE_KEY'),
        'customer_code' => env('BLUEDART_CUSTOMER_CODE'),
        'origin_pincode' => env('BLUEDART_ORIGIN_PINCODE'),
        'origin_area' => '',
        'sender' => '',
        'shipper_name' => env('APP_NAME', 'Store'),
        'shipper_address' => '',
        'shipper_mobile' => '',
        'product_code' => 'D',
        'sub_product_code' => '',
        'default_rate' => null,
    ],

    'dhl' => [
        'api_base_url' => env('DHL_API_BASE_URL', 'https://express.api.dhl.com/mydhlapi/test'),
        'api_key' => env('DHL_API_KEY'),
        'api_secret' => env('DHL_API_SECRET'),
        'account_number' => env('DHL_ACCOUNT_NUMBER'),
        'origin_country' => env('DHL_ORIGIN_COUNTRY', 'IN'),
        'origin_postal_code' => env('DHL_ORIGIN_POSTAL_CODE'),
        'origin_city' => '',
        'origin_address' => '',
        'shipper_name' => env('APP_NAME', 'Store'),
        'shipper_phone' => '',
        'shipper_email' => '',
        'currency' => 'INR',
        'default_product_code' => 'P',
    ],

    'delhivery' => [
        'api_base_url' => env('DELHIVERY_API_BASE_URL', 'https://staging-express.delhivery.com/api/cmu/create.json'),
        'rate_api_url' => env('DELHIVERY_RATE_API_URL', 'https://track.delhivery.com/api/kinko/v1/invoice/charges/.json'),
        'api_token' => env('DELHIVERY_API_TOKEN'),
    ],
];
