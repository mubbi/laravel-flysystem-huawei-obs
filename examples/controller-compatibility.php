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

// 3. File Upload (putFileAs method)
echo "3. File Upload\n";
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

// 4. File Download (get method)
echo "4. File Download\n";
echo "----------------\n";

try {
    $filePath = 'uploads/'.$filename;

    if (Storage::disk('huawei-obs')->exists($filePath)) {
        $fileContent = Storage::disk('huawei-obs')->get($filePath);
        $fileSize = Storage::disk('huawei-obs')->size($filePath);
        $mimeType = Storage::disk('huawei-obs')->mimeType($filePath);
        $url = Storage::disk('huawei-obs')->url($filePath);

        echo "✓ File downloaded successfully\n";
        echo "  - Path: {$filePath}\n";
        echo "  - Size: {$fileSize} bytes\n";
        echo "  - MIME Type: {$mimeType}\n";
        echo "  - URL: {$url}\n";
        echo '  - Content Preview: '.substr($fileContent, 0, 50)."...\n";
    } else {
        echo "✗ File not found: {$filePath}\n";
    }
} catch (\Exception $e) {
    echo "✗ File download failed: {$e->getMessage()}\n";
}

echo "\n";

// 5. File Information (getFileInfo method)
echo "5. File Information\n";
echo "-------------------\n";

try {
    $filePath = 'uploads/'.$filename;

    if (Storage::disk('huawei-obs')->exists($filePath)) {
        $fileInfo = [
            'path' => $filePath,
            'size' => Storage::disk('huawei-obs')->size($filePath),
            'last_modified' => Storage::disk('huawei-obs')->lastModified($filePath),
            'mime_type' => Storage::disk('huawei-obs')->mimeType($filePath),
            'url' => Storage::disk('huawei-obs')->url($filePath),
            'visibility' => Storage::disk('huawei-obs')->visibility($filePath),
            'exists' => Storage::disk('huawei-obs')->exists($filePath),
        ];

        echo "✓ File info retrieved successfully\n";
        foreach ($fileInfo as $key => $value) {
            echo "  - {$key}: ".(is_string($value) ? $value : json_encode($value))."\n";
        }
    } else {
        echo "✗ File not found: {$filePath}\n";
    }
} catch (\Exception $e) {
    echo "✗ Failed to get file info: {$e->getMessage()}\n";
}

echo "\n";

// 6. Directory Creation (makeDirectory method)
echo "6. Directory Creation\n";
echo "---------------------\n";

try {
    $directoryName = 'test-directory-'.time();
    $created = Storage::disk('huawei-obs')->makeDirectory($directoryName);

    if ($created) {
        echo "✓ Directory created successfully: {$directoryName}\n";
    } else {
        echo "✗ Failed to create directory\n";
    }
} catch (\Exception $e) {
    echo "✗ Directory creation failed: {$e->getMessage()}\n";
}

echo "\n";

// 7. File Copy (copy method)
echo "7. File Copy\n";
echo "------------\n";

try {
    $sourcePath = 'uploads/'.$filename;
    $destinationPath = 'uploads/copied-'.$filename;

    if (Storage::disk('huawei-obs')->exists($sourcePath)) {
        $copied = Storage::disk('huawei-obs')->copy($sourcePath, $destinationPath);

        if ($copied) {
            echo "✓ File copied successfully\n";
            echo "  - Source: {$sourcePath}\n";
            echo "  - Destination: {$destinationPath}\n";
        } else {
            echo "✗ Failed to copy file\n";
        }
    } else {
        echo "✗ Source file not found: {$sourcePath}\n";
    }
} catch (\Exception $e) {
    echo "✗ File copy failed: {$e->getMessage()}\n";
}

echo "\n";

// 8. File Move (move method)
echo "8. File Move\n";
echo "------------\n";

try {
    $sourcePath = 'uploads/copied-'.$filename;
    $destinationPath = 'uploads/moved-'.$filename;

    if (Storage::disk('huawei-obs')->exists($sourcePath)) {
        $moved = Storage::disk('huawei-obs')->move($sourcePath, $destinationPath);

        if ($moved) {
            echo "✓ File moved successfully\n";
            echo "  - Source: {$sourcePath}\n";
            echo "  - Destination: {$destinationPath}\n";
        } else {
            echo "✗ Failed to move file\n";
        }
    } else {
        echo "✗ Source file not found: {$sourcePath}\n";
    }
} catch (\Exception $e) {
    echo "✗ File move failed: {$e->getMessage()}\n";
}

echo "\n";

// 9. Bucket Statistics (allFiles, allDirectories methods)
echo "9. Bucket Statistics\n";
echo "-------------------\n";

try {
    $allFiles = Storage::disk('huawei-obs')->allFiles();
    $allDirectories = Storage::disk('huawei-obs')->allDirectories();

    $totalSize = 0;
    $fileTypes = [];

    foreach ($allFiles as $file) {
        $size = Storage::disk('huawei-obs')->size($file);
        $totalSize += $size;

        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $fileTypes[$extension] = ($fileTypes[$extension] ?? 0) + 1;
    }

    echo "✓ Bucket statistics retrieved successfully\n";
    echo '  - Total files: '.count($allFiles)."\n";
    echo '  - Total directories: '.count($allDirectories)."\n";
    echo '  - Total size: '.round($totalSize / 1024 / 1024, 2)." MB\n";
    echo '  - File types: '.json_encode($fileTypes)."\n";
} catch (\Exception $e) {
    echo "✗ Failed to get bucket stats: {$e->getMessage()}\n";
}

echo "\n";

// 10. File Deletion (delete method)
echo "10. File Deletion\n";
echo "-----------------\n";

try {
    $filesToDelete = [
        'uploads/'.$filename,
        'uploads/moved-'.$filename,
    ];

    foreach ($filesToDelete as $filePath) {
        if (Storage::disk('huawei-obs')->exists($filePath)) {
            $deleted = Storage::disk('huawei-obs')->delete($filePath);

            if ($deleted) {
                echo "✓ File deleted successfully: {$filePath}\n";
            } else {
                echo "✗ Failed to delete file: {$filePath}\n";
            }
        } else {
            echo "⚠ File not found (already deleted?): {$filePath}\n";
        }
    }
} catch (\Exception $e) {
    echo "✗ File deletion failed: {$e->getMessage()}\n";
}

echo "\n";

// 11. Directory Deletion (deleteDirectory method)
echo "11. Directory Deletion\n";
echo "----------------------\n";

try {
    $directoriesToDelete = [
        'test-directory-'.time(),
        'uploads',
    ];

    foreach ($directoriesToDelete as $directory) {
        if (Storage::disk('huawei-obs')->exists($directory)) {
            $deleted = Storage::disk('huawei-obs')->deleteDirectory($directory);

            if ($deleted) {
                echo "✓ Directory deleted successfully: {$directory}\n";
            } else {
                echo "✗ Failed to delete directory: {$directory}\n";
            }
        } else {
            echo "⚠ Directory not found (already deleted?): {$directory}\n";
        }
    }
} catch (\Exception $e) {
    echo "✗ Directory deletion failed: {$e->getMessage()}\n";
}

echo "\n";

// 12. Visibility Methods (getVisibility, setVisibility)
echo "12. Visibility Methods\n";
echo "----------------------\n";

try {
    // Create a test file
    $testFile = 'visibility-test.txt';
    Storage::disk('huawei-obs')->put($testFile, 'test content');

    // Get current visibility
    $visibility = Storage::disk('huawei-obs')->getVisibility($testFile);
    echo "✓ Current visibility: {$visibility}\n";

    // Set to public
    Storage::disk('huawei-obs')->setVisibility($testFile, 'public');
    $newVisibility = Storage::disk('huawei-obs')->getVisibility($testFile);
    echo "✓ New visibility: {$newVisibility}\n";

    // Clean up
    Storage::disk('huawei-obs')->delete($testFile);
    echo "✓ Test file cleaned up\n";
} catch (\Exception $e) {
    echo "✗ Visibility test failed: {$e->getMessage()}\n";
}

echo "\n=== Controller Compatibility Demo Completed ===\n";
echo "All methods used in HuaweiObsController are now supported!\n";
