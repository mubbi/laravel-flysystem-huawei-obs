<?php

/**
 * URL Usage Examples for Laravel Flysystem Huawei OBS Adapter
 *
 * This file demonstrates how to use the URL functionality in the package.
 *
 * @author  Mubbasher Ahmed <hello@mubbi.me>
 *
 * @link    https://mubbi.me
 *
 * @license MIT
 */

use Illuminate\Support\Facades\Storage;

echo "=== Huawei OBS URL Usage Examples ===\n\n";

// 1. Public URL for Public Objects
echo "1. Public URL for Public Objects\n";
echo "--------------------------------\n";

try {
    // This will work if the object is public
    $publicUrl = Storage::disk('huawei-obs')->url('public-file.txt');
    echo "✓ Public URL: {$publicUrl}\n";
} catch (\RuntimeException $e) {
    echo "✗ Public URL Error: {$e->getMessage()}\n";
}

echo "\n";

// 2. Signed URL for Any Object (Public or Private)
echo "2. Signed URL for Any Object\n";
echo "-----------------------------\n";

$adapter = Storage::disk('huawei-obs')->getAdapter();

try {
    // Create a signed URL for GET access (1 hour)
    $signedUrl = $adapter->createSignedUrl('any-file.txt', 'GET', 3600);
    echo '✓ Signed URL (GET): '.substr($signedUrl, 0, 50)."...\n";

    // Create a signed URL for PUT access (2 hours)
    $putSignedUrl = $adapter->createSignedUrl('any-file.txt', 'PUT', 7200);
    echo '✓ Signed URL (PUT): '.substr($putSignedUrl, 0, 50)."...\n";
} catch (\Exception $e) {
    echo "✗ Signed URL Error: {$e->getMessage()}\n";
}

echo "\n";

// 3. URL for Private Objects (Now Returns Signed URL)
echo "3. URL for Private Objects (Returns Signed URL)\n";
echo "-----------------------------------------------\n";

try {
    // This will now return a signed URL for private objects
    $privateUrl = Storage::disk('huawei-obs')->url('private-file.txt');
    echo "✓ Private URL (Signed): {$privateUrl}\n";
    echo "  → The url() method now automatically returns signed URLs for private objects\n";
} catch (\RuntimeException $e) {
    echo "✗ Error: {$e->getMessage()}\n";
}

echo "\n";

// 4. Laravel-Compatible URL Methods
echo "4. Laravel-Compatible URL Methods\n";
echo "---------------------------------\n";

try {
    // Using Laravel's temporaryUrl() method
    $temporaryUrl = Storage::disk('huawei-obs')->temporaryUrl('example-file.txt', now()->addHour());
    echo '✓ Temporary URL (Laravel): '.substr($temporaryUrl, 0, 50)."...\n";

    // Using Laravel's temporaryUrl() with custom options
    $temporaryUrlWithOptions = Storage::disk('huawei-obs')->temporaryUrl(
        'example-file.txt',
        now()->addHours(2),
        ['method' => 'PUT', 'headers' => ['Content-Type' => 'text/plain']]
    );
    echo '✓ Temporary URL with Options: '.substr($temporaryUrlWithOptions, 0, 50)."...\n";
} catch (\Exception $e) {
    echo "✗ Temporary URL Error: {$e->getMessage()}\n";
}

echo "\n";

// 5. Complete File Info Example
echo "5. Complete File Info Example\n";
echo "-----------------------------\n";

$filePath = 'example-file.txt';

if (Storage::disk('huawei-obs')->exists($filePath)) {
    $fileInfo = [
        'path' => $filePath,
        'size' => Storage::disk('huawei-obs')->size($filePath),
        'last_modified' => Storage::disk('huawei-obs')->lastModified($filePath),
        'mime_type' => Storage::disk('huawei-obs')->mimeType($filePath),
        'visibility' => Storage::disk('huawei-obs')->visibility($filePath),
        'exists' => Storage::disk('huawei-obs')->exists($filePath),
    ];

    // Try to get URL based on visibility
    try {
        if ($fileInfo['visibility'] === 'public') {
            $fileInfo['url'] = Storage::disk('huawei-obs')->url($filePath);
            $fileInfo['url_type'] = 'public';
        } else {
            $fileInfo['url'] = $adapter->createSignedUrl($filePath, 'GET', 3600);
            $fileInfo['url_type'] = 'signed';
        }
    } catch (\Exception $e) {
        $fileInfo['url'] = null;
        $fileInfo['url_error'] = $e->getMessage();
    }

    echo "✓ File Info:\n";
    foreach ($fileInfo as $key => $value) {
        echo "  {$key}: ".(is_string($value) ? $value : json_encode($value))."\n";
    }
} else {
    echo "✗ File not found: {$filePath}\n";
}

echo "\n=== End of Examples ===\n";
