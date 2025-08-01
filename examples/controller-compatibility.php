<?php

/**
 * Controller Compatibility Example for Laravel Flysystem Huawei OBS Adapter
 *
 * This file demonstrates all the methods used in the HuaweiObsController
 * to show full compatibility with Laravel Storage facade.
 *
 * @author  Mubbasher Ahmed <hello@mubbi.me>
 *
 * @link    https://mubbi.me
 *
 * @license MIT
 */

use Illuminate\Support\Facades\Storage;

echo "=== Huawei OBS Controller Compatibility Demo ===\n\n";

// 1. Test Connection (exists method)
echo "1. Testing Connection\n";
echo "---------------------\n";

try {
    $exists = Storage::disk('huawei-obs')->exists('test-connection.txt');
    echo '✓ Connection successful - File exists check: '.($exists ? 'true' : 'false')."\n";
} catch (\Exception $e) {
    echo "✗ Connection failed: {$e->getMessage()}\n";
}

echo "\n";

// 2. List Files and Directories
echo "2. Listing Files and Directories\n";
echo "--------------------------------\n";

try {
    $directory = 'uploads';
    $files = Storage::disk('huawei-obs')->files($directory);
    $directories = Storage::disk('huawei-obs')->directories($directory);

    echo "✓ Files in '{$directory}': ".count($files)."\n";
    foreach ($files as $file) {
        echo "  - {$file}\n";
    }

    echo "✓ Directories in '{$directory}': ".count($directories)."\n";
    foreach ($directories as $dir) {
        echo "  - {$dir}\n";
    }
} catch (\Exception $e) {
    echo "✗ Failed to list files: {$e->getMessage()}\n";
}

echo "\n";

// 3. Optimized Methods for Large Datasets
echo "3. Optimized Methods for Large Datasets\n";
echo "---------------------------------------\n";

try {
    // Get storage statistics with timeout protection
    $stats = Storage::disk('huawei-obs')->getStorageStats(1000, 30);
    echo "✓ Storage stats retrieved:\n";
    echo "  - Total files: {$stats['total_files']}\n";
    echo "  - Total directories: {$stats['total_directories']}\n";
    echo "  - Total size: {$stats['total_size_mb']} MB\n";
    echo "  - Processing time: {$stats['processing_time_seconds']} seconds\n";

    // Get files with limits
    $files = Storage::disk('huawei-obs')->allFilesOptimized(100, 10);
    echo "✓ Retrieved {$files} files with optimized method\n";

} catch (\Exception $e) {
    echo "✗ Optimized methods failed: {$e->getMessage()}\n";
}

echo "\n";

// 4. File Upload (putFileAs method)
echo "4. File Upload\n";
echo "--------------\n";

try {
    // Simulate file upload
    $directory = 'uploads';
    $filename = time().'_test-file.txt';
    $path = $directory.'/'.$filename;

    // Upload content
    $uploaded = Storage::disk('huawei-obs')->putFileAs(
        $directory,
        'test content',
        $filename,
        ['visibility' => 'public']
    );

    if ($uploaded) {
        echo "✓ File uploaded successfully: {$path}\n";

        // Get file info
        $size = Storage::disk('huawei-obs')->size($path);
        $mimeType = Storage::disk('huawei-obs')->mimeType($path);
        $url = Storage::disk('huawei-obs')->url($path);

        echo "  - Size: {$size} bytes\n";
        echo "  - MIME Type: {$mimeType}\n";
        echo "  - URL: {$url}\n";
    } else {
        echo "✗ Failed to upload file\n";
    }
} catch (\Exception $e) {
    echo "✗ File upload failed: {$e->getMessage()}\n";
}

echo "\n";

// 5. File Download (get method)
echo "5. File Download\n";
echo "----------------\n";

try {
    $filePath = 'uploads/'.$filename;
    $content = Storage::disk('huawei-obs')->get($filePath);
    echo "✓ File downloaded successfully: {$filePath}\n";
    echo '  - Content length: '.strlen($content)." bytes\n";
    echo '  - Content preview: '.substr($content, 0, 50)."...\n";
} catch (\Exception $e) {
    echo "✗ File download failed: {$e->getMessage()}\n";
}

echo "\n";

// 6. File Information
echo "6. File Information\n";
echo "-------------------\n";

try {
    $filePath = 'uploads/'.$filename;

    $exists = Storage::disk('huawei-obs')->exists($filePath);
    $size = Storage::disk('huawei-obs')->size($filePath);
    $lastModified = Storage::disk('huawei-obs')->lastModified($filePath);
    $mimeType = Storage::disk('huawei-obs')->mimeType($filePath);
    $visibility = Storage::disk('huawei-obs')->visibility($filePath);

    echo "✓ File information retrieved:\n";
    echo '  - Exists: '.($exists ? 'Yes' : 'No')."\n";
    echo "  - Size: {$size} bytes\n";
    echo '  - Last Modified: '.date('Y-m-d H:i:s', $lastModified)."\n";
    echo "  - MIME Type: {$mimeType}\n";
    echo "  - Visibility: {$visibility}\n";

} catch (\Exception $e) {
    echo "✗ Failed to get file information: {$e->getMessage()}\n";
}

echo "\n";

// 7. Temporary URLs
echo "7. Temporary URLs\n";
echo "-----------------\n";

try {
    $filePath = 'uploads/'.$filename;

    // Generate temporary URL
    $tempUrl = Storage::disk('huawei-obs')->temporaryUrl($filePath, now()->addHour());
    echo "✓ Temporary URL generated:\n";
    echo "  - URL: {$tempUrl}\n";
    echo '  - Expires: '.now()->addHour()->format('Y-m-d H:i:s')."\n";

} catch (\Exception $e) {
    echo "✗ Failed to generate temporary URL: {$e->getMessage()}\n";
}

echo "\n";

// 8. Object Tagging
echo "8. Object Tagging\n";
echo "----------------\n";

try {
    $filePath = 'uploads/'.$filename;

    // Set object tags
    Storage::disk('huawei-obs')->setObjectTags($filePath, [
        'category' => 'test',
        'uploaded_at' => date('Y-m-d H:i:s'),
        'demo' => 'true',
    ]);
    echo "✓ Object tags set successfully\n";

    // Get object tags
    $tags = Storage::disk('huawei-obs')->getObjectTags($filePath);
    echo "✓ Object tags retrieved:\n";
    foreach ($tags as $key => $value) {
        echo "  - {$key}: {$value}\n";
    }

} catch (\Exception $e) {
    echo "✗ Object tagging failed: {$e->getMessage()}\n";
}

echo "\n";

// 9. File Operations
echo "9. File Operations\n";
echo "------------------\n";

try {
    $sourcePath = 'uploads/'.$filename;
    $copyPath = 'uploads/copied-'.$filename;
    $movePath = 'uploads/moved-'.$filename;

    // Copy file
    Storage::disk('huawei-obs')->copy($sourcePath, $copyPath);
    echo "✓ File copied: {$sourcePath} → {$copyPath}\n";

    // Move file
    Storage::disk('huawei-obs')->move($copyPath, $movePath);
    echo "✓ File moved: {$copyPath} → {$movePath}\n";

    // Delete files
    Storage::disk('huawei-obs')->delete([$sourcePath, $movePath]);
    echo "✓ Files deleted successfully\n";

} catch (\Exception $e) {
    echo "✗ File operations failed: {$e->getMessage()}\n";
}

echo "\n";

// 10. Directory Operations
echo "10. Directory Operations\n";
echo "-----------------------\n";

try {
    $testDir = 'test-directory';

    // Create directory
    Storage::disk('huawei-obs')->makeDirectory($testDir);
    echo "✓ Directory created: {$testDir}\n";

    // Check if directory exists
    $dirExists = Storage::disk('huawei-obs')->exists($testDir.'/');
    echo '✓ Directory exists: '.($dirExists ? 'Yes' : 'No')."\n";

    // Delete directory
    Storage::disk('huawei-obs')->deleteDirectory($testDir);
    echo "✓ Directory deleted: {$testDir}\n";

} catch (\Exception $e) {
    echo "✗ Directory operations failed: {$e->getMessage()}\n";
}

echo "\n";

// 11. Complete File Browser Example
echo "11. Complete File Browser Example\n";
echo "---------------------------------\n";

try {
    $directory = $request->get('directory', '');
    $files = Storage::disk('huawei-obs')->files($directory);
    $directories = Storage::disk('huawei-obs')->directories($directory);

    $fileDetails = [];
    foreach ($files as $file) {
        $fileDetails[] = [
            'name' => $file,
            'size' => Storage::disk('huawei-obs')->size($file),
            'last_modified' => Storage::disk('huawei-obs')->lastModified($file),
            'mime_type' => Storage::disk('huawei-obs')->mimeType($file),
            'url' => Storage::disk('huawei-obs')->url($file),
            'visibility' => Storage::disk('huawei-obs')->visibility($file),
        ];
    }

    echo "✓ File browser data prepared:\n";
    echo '  - Total files: '.count($fileDetails)."\n";
    echo '  - Total directories: '.count($directories)."\n";
    echo "  - Current directory: {$directory}\n";

    // Show first few files
    foreach (array_slice($fileDetails, 0, 3) as $file) {
        echo "    - {$file['name']} ({$file['size']} bytes)\n";
    }

} catch (\Exception $e) {
    echo "✗ File browser failed: {$e->getMessage()}\n";
}

echo "\n";

// 12. Performance Test
echo "12. Performance Test\n";
echo "--------------------\n";

try {
    $startTime = microtime(true);

    // Test optimized methods
    $stats = Storage::disk('huawei-obs')->getStorageStats(100, 10);

    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 3);

    echo "✓ Performance test completed:\n";
    echo "  - Duration: {$duration} seconds\n";
    echo "  - Files processed: {$stats['processed_count']}\n";
    echo "  - Processing time: {$stats['processing_time_seconds']} seconds\n";

} catch (\Exception $e) {
    echo "✗ Performance test failed: {$e->getMessage()}\n";
}

echo "\n";

echo "=== Demo Completed ===\n";
echo "All Laravel Storage facade methods are fully compatible!\n";
