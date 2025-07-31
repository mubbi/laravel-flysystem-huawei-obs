<?php

/**
 * Advanced Usage Examples for Laravel Flysystem Huawei OBS Adapter
 *
 * This file demonstrates all the advanced features available in the package.
 *
 * @author  Mubbasher Ahmed <hello@mubbi.me>
 *
 * @link    https://mubbi.me
 *
 * @license MIT
 */

use Illuminate\Support\Facades\Storage;
use LaravelFlysystemHuaweiObs\HuaweiObsAdapter;

// Get the adapter instance
$adapter = Storage::disk('huawei-obs')->getAdapter();

echo "=== Huawei OBS Advanced Features Demo ===\n\n";

// 1. Temporary Credentials (Security Token)
echo "1. Temporary Credentials Support\n";
echo "--------------------------------\n";

// Configure with security token for temporary credentials
$adapterWithToken = new HuaweiObsAdapter(
    'access_key_id',
    'secret_access_key',
    'bucket_name',
    'endpoint',
    null, // prefix
    null, // http client
    'security_token_here' // temporary credentials
);

// Refresh credentials during runtime (useful for long-running applications)
$adapter->refreshCredentials('new_access_key', 'new_secret_key', 'new_security_token');
echo "✓ Credentials refreshed successfully\n\n";

// 2. Signed URLs
echo "2. Signed URLs for Temporary Access\n";
echo "-----------------------------------\n";

// Create a signed URL for GET access (1 hour)
$signedUrl = $adapter->createSignedUrl('documents/report.pdf', 'GET', 3600);
echo '✓ GET signed URL created: '.substr($signedUrl, 0, 50)."...\n";

// Create a signed URL for PUT access with custom headers
$putSignedUrl = $adapter->createSignedUrl('uploads/file.txt', 'PUT', 7200, [
    'Content-Type' => 'text/plain',
    'x-amz-acl' => 'public-read',
]);
echo '✓ PUT signed URL created: '.substr($putSignedUrl, 0, 50)."...\n\n";

// 3. Post Signatures for Direct Browser Uploads
echo "3. Post Signatures for Direct Uploads\n";
echo "-------------------------------------\n";

// Create post signature for direct browser upload
$signature = $adapter->createPostSignature('uploads/user-file.txt', [
    ['starts-with', '$key', 'uploads/'],
    ['content-length-range', 0, 10485760], // 10MB max
    ['eq', '$Content-Type', 'text/plain'],
], 3600);

echo "✓ Post signature created:\n";
echo '  - Policy: '.substr($signature['Policy'], 0, 30)."...\n";
echo '  - Signature: '.substr($signature['Signature'], 0, 30)."...\n\n";

// 4. Object Tagging
echo "4. Object Tagging (Metadata)\n";
echo "----------------------------\n";

// Set object tags
$adapter->setObjectTags('images/profile.jpg', [
    'environment' => 'production',
    'type' => 'profile_image',
    'user_id' => '12345',
    'uploaded_by' => 'web_interface',
]);
echo "✓ Object tags set successfully\n";

// Get object tags
$tags = $adapter->getObjectTags('images/profile.jpg');
echo '✓ Object tags retrieved: '.json_encode($tags)."\n";

// Delete object tags
$adapter->deleteObjectTags('images/profile.jpg');
echo "✓ Object tags deleted successfully\n\n";

// 5. Object Restoration
echo "5. Object Restoration\n";
echo "---------------------\n";

// Restore an archived object for 7 days
$adapter->restoreObject('archived/old-document.pdf', 7);
echo "✓ Object restoration initiated for 7 days\n\n";

// 6. Multi-Tenant Support
echo "6. Multi-Tenant Support\n";
echo "----------------------\n";

// Create tenant-specific adapters
$tenant1Adapter = new HuaweiObsAdapter(
    'access_key_id',
    'secret_access_key',
    'shared_bucket',
    'endpoint',
    'tenant1' // prefix for tenant 1
);

$tenant2Adapter = new HuaweiObsAdapter(
    'access_key_id',
    'secret_access_key',
    'shared_bucket',
    'endpoint',
    'tenant2' // prefix for tenant 2
);

// Files will be stored with prefixes
$tenant1Adapter->write('document.txt', 'Tenant 1 content');
$tenant2Adapter->write('document.txt', 'Tenant 2 content');

echo "✓ Multi-tenant files created:\n";
echo "  - Tenant 1: tenant1/document.txt\n";
echo "  - Tenant 2: tenant2/document.txt\n\n";

// 7. Custom HTTP Client Configuration
echo "7. Custom HTTP Client Configuration\n";
echo "-----------------------------------\n";

// Create adapter with custom HTTP client settings
$customHttpClient = new \GuzzleHttp\Client([
    'timeout' => 60,
    'connect_timeout' => 20,
    'verify' => false, // Disable SSL verification for testing
    'proxy' => 'http://proxy.example.com:8080',
    'headers' => [
        'User-Agent' => 'MyApp/1.0',
        'X-Custom-Header' => 'custom-value',
    ],
]);

$customAdapter = new HuaweiObsAdapter(
    'access_key_id',
    'secret_access_key',
    'bucket_name',
    'endpoint',
    null, // prefix
    $customHttpClient
);

echo "✓ Custom HTTP client configured\n\n";

// 8. Error Handling Examples
echo "8. Error Handling\n";
echo "-----------------\n";

try {
    // Try to access a non-existent file
    $adapter->read('non-existent-file.txt');
} catch (\League\Flysystem\UnableToReadFile $e) {
    echo '✓ Caught read error: '.$e->getMessage()."\n";
}

try {
    // Try to create a signed URL for a non-existent file
    $adapter->createSignedUrl('non-existent-file.txt');
} catch (\RuntimeException $e) {
    echo '✓ Caught signed URL error: '.$e->getMessage()."\n";
}

echo "\n=== Demo Complete ===\n";

// 9. HTML Form Example for Direct Upload
echo "\n9. HTML Form for Direct Upload\n";
echo "------------------------------\n";

$uploadSignature = $adapter->createPostSignature('uploads/direct-upload.txt', [
    ['starts-with', '$key', 'uploads/'],
    ['content-length-range', 0, 5242880], // 5MB max
], 1800); // 30 minutes

echo "HTML Form for direct upload:\n";
echo '<form action="'.$uploadSignature['url']."\" method=\"post\" enctype=\"multipart/form-data\">\n";
echo '  <input type="hidden" name="policy" value="'.$uploadSignature['Policy']."\">\n";
echo '  <input type="hidden" name="signature" value="'.$uploadSignature['Signature']."\">\n";
echo "  <input type=\"file\" name=\"file\" accept=\"text/*\">\n";
echo "  <input type=\"submit\" value=\"Upload Directly to OBS\">\n";
echo "</form>\n\n";

echo "=== All Advanced Features Demonstrated ===\n";
