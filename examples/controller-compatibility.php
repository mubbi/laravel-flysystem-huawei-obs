<?php

declare(strict_types=1);

// Example controller snippet for listing files/directories using Laravel's Storage facade

// namespace App\Http\Controllers;
//
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Storage;
//
// class ObsBrowserController
// {
//     public function index(Request $request)
//     {
//         $disk = Storage::disk('huawei-obs');
//         $directory = $request->get('directory', '');
//
//         $files = $disk->files($directory);
//         $directories = $disk->directories($directory);
//
//         $fileDetails = [];
//         foreach ($files as $file) {
//             $fileDetails[] = [
//                 'name' => $file,
//                 'size' => $disk->size($file),
//                 'last_modified' => $disk->lastModified($file),
//                 'mime_type' => $disk->mimeType($file),
//                 'url' => $disk->url($file),
//                 'visibility' => $disk->visibility($file),
//             ];
//         }
//
//         return response()->json([
//             'files' => $fileDetails,
//             'directories' => $directories,
//         ]);
//     }
// }
