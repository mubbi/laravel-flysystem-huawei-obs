<?php

declare(strict_types=1);

// URL generation examples using Laravel's Storage facade macros

// In Laravel, after configuring the 'huawei-obs' disk:
//
// use Illuminate\Support\Facades\Storage;
//
// // Public or private-aware URL
// $url = Storage::disk('huawei-obs')->url('path/to/file.txt');
//
// // Temporary URL with custom expiry
// $tempUrl = Storage::disk('huawei-obs')->temporaryUrl('path/to/file.txt', now()->addMinutes(30));
//
// // Temporary upload URL (PUT)
// $uploadUrl = Storage::disk('huawei-obs')->temporaryUploadUrl('uploads/file.txt', now()->addHour());
