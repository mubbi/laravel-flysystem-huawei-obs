<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3", "huawei-obs"
    |
    */

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

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        // Huawei OBS Configuration with optimized timeout settings
        'huawei-obs' => [
            'driver' => 'huawei-obs',
            'key' => env('HUAWEI_OBS_ACCESS_KEY_ID'),
            'secret' => env('HUAWEI_OBS_SECRET_ACCESS_KEY'),
            'bucket' => env('HUAWEI_OBS_BUCKET'),
            'endpoint' => env('HUAWEI_OBS_ENDPOINT'),
            'prefix' => env('HUAWEI_OBS_PREFIX'),

            // HTTP Client configuration for better performance
            'http_client' => [
                'timeout' => env('HUAWEI_OBS_TIMEOUT', 120), // 2 minutes total timeout
                'connect_timeout' => env('HUAWEI_OBS_CONNECT_TIMEOUT', 30), // 30 seconds connection timeout
                'verify' => env('HUAWEI_OBS_VERIFY_SSL', true), // SSL verification
            ],

            // Retry configuration
            'retry_attempts' => env('HUAWEI_OBS_RETRY_ATTEMPTS', 3),
            'retry_delay' => env('HUAWEI_OBS_RETRY_DELAY', 1),

            // Logging configuration
            'logging_enabled' => env('HUAWEI_OBS_LOGGING_ENABLED', false),
            'log_operations' => env('HUAWEI_OBS_LOG_OPERATIONS', false),
            'log_errors' => env('HUAWEI_OBS_LOG_ERRORS', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
