<?php

declare(strict_types=1);

// Basic usage examples for Laravel Flysystem Huawei OBS Adapter

// 1) In a Laravel application (using the Storage facade):
//
// In config/filesystems.php, add a disk named 'huawei-obs' as shown in README.
// Then you can use:
//
// use Illuminate\Support\Facades\Storage;
//
// Storage::disk('huawei-obs')->put('file.txt', 'Hello World');
// $contents = Storage::disk('huawei-obs')->get('file.txt');
// $exists = Storage::disk('huawei-obs')->exists('file.txt');
// Storage::disk('huawei-obs')->delete('file.txt');

// 2) Direct Flysystem usage without Laravel (standalone PHP):
//
// composer require mubbi/laravel-flysystem-huawei-obs
//
// Then:

use LaravelFlysystemHuaweiObs\LaravelHuaweiObsAdapter;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;

// Replace with your environment values
$accessKeyId = getenv('HUAWEI_OBS_ACCESS_KEY_ID') ?: 'your-access-key';
$secretKey = getenv('HUAWEI_OBS_SECRET_ACCESS_KEY') ?: 'your-secret-key';
$bucket = getenv('HUAWEI_OBS_BUCKET') ?: 'your-bucket';
$endpoint = getenv('HUAWEI_OBS_ENDPOINT') ?: 'https://obs.cn-north-1.myhuaweicloud.com';
$prefix = getenv('HUAWEI_OBS_PREFIX') ?: null;
$token = getenv('HUAWEI_OBS_SECURITY_TOKEN') ?: null; // optional temporary credentials

$adapter = new LaravelHuaweiObsAdapter(
    $accessKeyId,
    $secretKey,
    $bucket,
    $endpoint,
    $prefix,
    httpClient: null,
    securityToken: $token,
);

$filesystem = new Filesystem($adapter);

// Write and read
$filesystem->write('demo/example.txt', 'Hello World', new Config);
$contents = $filesystem->read('demo/example.txt');
echo "Read back: {$contents}\n";
