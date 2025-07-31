<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        'huawei-obs' => [
            'driver' => 'huawei-obs',
            'key' => env('HUAWEI_OBS_ACCESS_KEY_ID'),
            'secret' => env('HUAWEI_OBS_SECRET_ACCESS_KEY'),
            'bucket' => env('HUAWEI_OBS_BUCKET'),
            'endpoint' => env('HUAWEI_OBS_ENDPOINT'),
            'region' => env('HUAWEI_OBS_REGION'),
            'prefix' => env('HUAWEI_OBS_PREFIX'),
            'security_token' => env('HUAWEI_OBS_SECURITY_TOKEN'),
            'visibility' => 'public',
            'throw' => false,
            'http_client' => [
                'timeout' => 30,
                'connect_timeout' => 10,
                'verify' => true,
                'proxy' => null,
                'headers' => [],
            ],
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
