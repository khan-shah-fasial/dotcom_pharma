<?php

return [
    // Set to "gemini" or "vision" to choose provider
    'provider' => env('IMAGE_SEARCH_PROVIDER', 'gemini'),
    // Dump full provider response to storage/logs/image-search-*.json (may include sensitive OCR text)
    'debug_full' => (bool) env('IMAGE_SEARCH_LOG_FULL', false),
    // Token tuning
    'max_tokens' => (int) env('IMAGE_SEARCH_MAX_TOKENS', 6),
    'min_terms'  => (int) env('IMAGE_SEARCH_MIN_TERMS', 2),
    // Verbose trace logging (adds flow breadcrumbs)
    'debug_trace' => (bool) env('IMAGE_SEARCH_DEBUG_TRACE', false),

    'gemini' => [
        'endpoint' => env('GOOGLE_GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent'),
        'api_key'  => env('GOOGLE_GEMINI_API_KEY', ''),
    ],

    'vision' => [
        'endpoint' => env('GOOGLE_VISION_ENDPOINT', 'https://vision.googleapis.com/v1/images:annotate'),
        'api_key'  => env('GOOGLE_VISION_API_KEY', ''),
    ],
];
