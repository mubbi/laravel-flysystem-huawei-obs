<?php

declare(strict_types=1);

// Advanced features via Laravel Storage macros registered by the service provider

// use Illuminate\Support\Facades\Storage;

// Signed URL (GET by default)
// $signed = Storage::disk('huawei-obs')->createSignedUrl('reports/monthly.csv', 'GET', 3600);

// Post signature for direct browser uploads
// $post = Storage::disk('huawei-obs')->createPostSignature('uploads/image.jpg', [
//     ['content-length-range', 0, 10485760], // up to 10MB
// ], 3600);

// Object tagging
// Storage::disk('huawei-obs')->setObjectTags('path/to/file.txt', ['category' => 'images', 'processed' => 'true']);
// $tags = Storage::disk('huawei-obs')->getObjectTags('path/to/file.txt');
// Storage::disk('huawei-obs')->deleteObjectTags('path/to/file.txt');

// Restore archived object
// Storage::disk('huawei-obs')->restoreObject('archive/file.gz', 7);

// Optimized listing and stats
// $files = Storage::disk('huawei-obs')->allFilesOptimized(1000, 30);
// $dirs = Storage::disk('huawei-obs')->allDirectoriesOptimized(1000, 30);
// $stats = Storage::disk('huawei-obs')->getStorageStats(1000, 30);
