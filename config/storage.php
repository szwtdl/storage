<?php
return [
    'default' => [
        'storage_type' => env('STORAGE_TYPE', 'aliyun'),
        'storage_secret_id' => env('STORAGE_SECRET_ID', ''),
        'storage_secret_key' => env('STORAGE_SECRET_KEY', ''),
        'storage_region' => env('STORAGE_REGION', ''),
        'storage_bucket' => env('STORAGE_BUCKET', ''),
        'storage_domain' => env('STORAGE_DOMAIN', ''),
    ],
    'aliyun' => [
        'storage_type' => env('STORAGE_TYPE', 'aliyun'),
        'storage_secret_id' => env('STORAGE_SECRET_ID', ''),
        'storage_secret_key' => env('STORAGE_SECRET_KEY', ''),
        'storage_region' => env('STORAGE_REGION', ''),
        'storage_bucket' => env('STORAGE_BUCKET', ''),
        'storage_domain' => env('STORAGE_DOMAIN', ''),
    ],
    'tencent' => [
        'storage_type' => env('STORAGE_TYPE', 'tencent'),
        'storage_secret_id' => env('STORAGE_SECRET_ID', ''),
        'storage_secret_key' => env('STORAGE_SECRET_KEY', ''),
        'storage_region' => env('STORAGE_REGION', ''),
        'storage_bucket' => env('STORAGE_BUCKET', ''),
        'storage_domain' => env('STORAGE_DOMAIN', ''),
    ]
];
