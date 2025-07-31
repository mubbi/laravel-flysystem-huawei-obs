<?php

declare(strict_types=1);

/**
 * Example usage of Laravel Flysystem Huawei OBS Adapter
 *
 * This file demonstrates various ways to use the Huawei OBS adapter
 * in a Laravel application.
 *
 * @author  Mubbasher Ahmed <hello@mubbi.me>
 *
 * @link    https://mubbi.me
 *
 * @license MIT
 */

use Illuminate\Support\Facades\Storage;
use League\Flysystem\Visibility;

// Basic file operations
echo "=== Basic File Operations ===\n";

// Store a file
Storage::disk('huawei-obs')->put('example.txt', 'Hello from Huawei OBS!');
echo "File stored successfully\n";

// Check if file exists
if (Storage::disk('huawei-obs')->exists('example.txt')) {
    echo "File exists\n";
}

// Get file contents
$contents = Storage::disk('huawei-obs')->get('example.txt');
echo "File contents: {$contents}\n";

// Get file size
$size = Storage::disk('huawei-obs')->size('example.txt');
echo "File size: {$size} bytes\n";

// Get last modified time
$modified = Storage::disk('huawei-obs')->lastModified('example.txt');
echo 'Last modified: '.date('Y-m-d H:i:s', $modified)."\n";

// Get mime type
$mimeType = Storage::disk('huawei-obs')->mimeType('example.txt');
echo "MIME type: {$mimeType}\n";

// File uploads
echo "\n=== File Uploads ===\n";

// Upload with custom filename
Storage::disk('huawei-obs')->putFileAs(
    'uploads',
    'example.txt',
    'custom-name.txt',
    ['visibility' => Visibility::PUBLIC]
);
echo "File uploaded with custom name\n";

// Directory operations
echo "\n=== Directory Operations ===\n";

// Create a directory
Storage::disk('huawei-obs')->makeDirectory('documents');
echo "Directory created\n";

// List files in directory
$files = Storage::disk('huawei-obs')->files('uploads');
echo 'Files in uploads directory: '.implode(', ', $files)."\n";

// List directories
$directories = Storage::disk('huawei-obs')->directories('');
echo 'Directories: '.implode(', ', $directories)."\n";

// File operations
echo "\n=== File Operations ===\n";

// Copy a file
Storage::disk('huawei-obs')->copy('example.txt', 'example-copy.txt');
echo "File copied\n";

// Move a file
Storage::disk('huawei-obs')->move('example-copy.txt', 'documents/example-moved.txt');
echo "File moved\n";

// File visibility
echo "\n=== File Visibility ===\n";

// Set file to public
Storage::disk('huawei-obs')->setVisibility('example.txt', Visibility::PUBLIC);
echo "File visibility set to public\n";

// Get file visibility
$visibility = Storage::disk('huawei-obs')->getVisibility('example.txt');
echo "File visibility: {$visibility}\n";

// Stream operations
echo "\n=== Stream Operations ===\n";

// Write from stream
$stream = fopen('php://temp', 'r+');
fwrite($stream, 'Stream content');
rewind($stream);

Storage::disk('huawei-obs')->writeStream('stream.txt', $stream);
echo "Stream written\n";

// Read to stream
$readStream = Storage::disk('huawei-obs')->readStream('stream.txt');
$streamContent = stream_get_contents($readStream);
fclose($readStream);
echo "Stream content: {$streamContent}\n";

// Cleanup
echo "\n=== Cleanup ===\n";

// Delete files
Storage::disk('huawei-obs')->delete([
    'example.txt',
    'stream.txt',
    'uploads/custom-name.txt',
    'documents/example-moved.txt',
]);
echo "Files deleted\n";

// Delete directories
Storage::disk('huawei-obs')->deleteDirectory('uploads');
Storage::disk('huawei-obs')->deleteDirectory('documents');
echo "Directories deleted\n";

echo "\nExample completed successfully!\n";
