<?php

return [
    'bluedart' => [
        // sandbox | production. All calls use the same host. Going live = live keys + production.
        // When unset, the host is taken from BLUEDART_API_BASE_URL.
        'environment' => env('BLUEDART_ENV'),
        'hosts' => [
            'sandbox' => 'https://apigateway-sandbox.bluedart.com',
            'production' => 'https://apigateway.bluedart.com',
        ],
        'api_base_url' => env('BLUEDART_API_BASE_URL'),
        'jwt_token' => env('BLUEDART_JWT_TOKEN'),
        'client_id' => env('BLUEDART_CLIENT_ID'),
        'client_secret' => env('BLUEDART_CLIENT_SECRET'),
        'login_id' => env('BLUEDART_LOGIN_ID'),
        'licence_key' => env('BLUEDART_LICENCE_KEY'),
        'customer_code' => env('BLUEDART_CUSTOMER_CODE'),
        'origin_pincode' => env('BLUEDART_ORIGIN_PINCODE'),
        'origin_area' => env('BLUEDART_ORIGIN_AREA'),
        'sender' => env('BLUEDART_SENDER'),
        'shipper_name' => env('APP_NAME', 'Store'),
        'shipper_address' => env('BLUEDART_SHIPPER_ADDRESS'),
        'shipper_mobile' => env('BLUEDART_SHIPPER_MOBILE'),
        'product_code' => env('BLUEDART_PRODUCT_CODE', 'D'),
        'sub_product_code' => env('BLUEDART_SUB_PRODUCT_CODE', 'P'),
        'default_rate' => env('BLUEDART_DEFAULT_RATE'),
    ],

    'dhl' => [
        // test | production. Going live = live keys + production. When unset, DHL_API_BASE_URL is used.
        'environment' => env('DHL_ENV'),
        'bases' => [
            'test' => 'https://express.api.dhl.com/mydhlapi/test',
            'production' => 'https://express.api.dhl.com/mydhlapi',
        ],
        'api_base_url' => env('DHL_API_BASE_URL'),
        'api_key' => env('DHL_API_KEY'),
        'api_secret' => env('DHL_API_SECRET'),
        'account_number' => env('DHL_ACCOUNT_NUMBER'),
        'origin_country' => env('DHL_ORIGIN_COUNTRY', 'IN'),
        'origin_postal_code' => env('DHL_ORIGIN_POSTAL_CODE'),
        'origin_city' => env('DHL_ORIGIN_CITY'),
        'origin_address' => env('DHL_ORIGIN_ADDRESS'),
        'shipper_name' => env('APP_NAME', 'Store'),
        'shipper_phone' => env('DHL_SHIPPER_PHONE'),
        'shipper_email' => env('DHL_SHIPPER_EMAIL'),
        'currency' => 'INR',
        'default_product_code' => 'P',
    ],

    'delhivery' => [
        // staging | production. Every Delhivery call (create, rates, pincode, label,
        // tracking, cancel, pickup) goes to the same host, so a staging token is never
        // sent to production. When unset, the host is taken from DELHIVERY_API_BASE_URL.
        'environment' => env('DELHIVERY_ENV'),
        'hosts' => [
            'staging' => 'https://staging-express.delhivery.com',
            'production' => 'https://track.delhivery.com',
        ],
        'api_base_url' => env('DELHIVERY_API_BASE_URL', 'https://staging-express.delhivery.com/api/cmu/create.json'),
        'api_token' => env('DELHIVERY_API_TOKEN'),
    ],
];
