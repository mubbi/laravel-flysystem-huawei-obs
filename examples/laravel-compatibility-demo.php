<?php

declare(strict_types=1);

// Demonstrates Laravel Storage facade compatibility methods available on the disk

// use Illuminate\Support\Facades\Storage;
//
// $disk = Storage::disk('huawei-obs');
//
// // Standard operations
// $disk->put('example.txt', 'Hello');
// $exists = $disk->exists('example.txt');
// $size = $disk->size('example.txt');
// $modified = $disk->lastModified('example.txt');
// $mime = $disk->mimeType('example.txt');
// $url = $disk->url('example.txt');
//
// // Optimized helpers
// $allFiles = $disk->allFilesOptimized(500, 20);
// $allDirs = $disk->allDirectoriesOptimized(500, 20);
// $stats = $disk->getStorageStats(1000, 30);
