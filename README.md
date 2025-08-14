# Laravel Flysystem Huawei OBS Adapter

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mubbi/laravel-flysystem-huawei-obs.svg?style=flat-square)](https://packagist.org/packages/mubbi/laravel-flysystem-huawei-obs)
[![codecov](https://codecov.io/github/mubbi/laravel-flysystem-huawei-obs/graph/badge.svg?token=UQRPIGQORG)](https://codecov.io/github/mubbi/laravel-flysystem-huawei-obs)
[![Total Downloads](https://poser.pugx.org/mubbi/laravel-flysystem-huawei-obs/downloads.svg)](https://packagist.org/packages/mubbi/laravel-flysystem-huawei-obs)
[![GitHub CI](https://img.shields.io/github/actions/workflow/status/mubbi/laravel-flysystem-huawei-obs/ci.yml?branch=main&label=CI&style=flat-square)](https://github.com/mubbi/laravel-flysystem-huawei-obs/actions/workflows/ci.yml)
[![License](https://img.shields.io/github/license/mubbi/laravel-flysystem-huawei-obs.svg?style=flat-square)](https://github.com/mubbi/laravel-flysystem-huawei-obs/blob/main/LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/mubbi/laravel-flysystem-huawei-obs.svg?style=flat-square)](https://packagist.org/packages/mubbi/laravel-flysystem-huawei-obs)
[![Laravel Version](https://img.shields.io/badge/Laravel-9%2B-red.svg?style=flat-square)](https://laravel.com)
[![Flysystem Version](https://img.shields.io/badge/Flysystem-v2%20%7C%20v3-blue.svg?style=flat-square)](https://flysystem.thephpleague.com/)
[![Guzzle Version](https://img.shields.io/badge/Guzzle-v6%20%7C%20v7%20%7C%20v8-green.svg?style=flat-square)](https://docs.guzzlephp.org/)

![Laravel Flysystem Huawei OBS Adapter](https://repository-images.githubusercontent.com/1029901662/58cf3e8d-a2f0-4994-8acd-9a80b8ef5e3c)

A Laravel Flysystem v2/v3 adapter for Huawei Object Storage Service (OBS). This package provides seamless integration between Laravel's filesystem abstraction and Huawei Cloud OBS, allowing you to use Huawei OBS as a storage backend in your Laravel applications.

## Multi-Version Compatibility

This package now supports multiple versions of both Flysystem and Guzzle:

- **Flysystem**: v3.0+ (primary) with v2.0+ compatibility through dependency constraints
- **Guzzle**: v6.3.0+, v7.0+, and v8.0+ (automatic detection)
- **Laravel**: 9.0+, 10.0+, 11.0+, and 12.0+
- **PHP**: 8.1+

## Features

- ✅ **Complete Flysystem v3 Compatibility**: Full implementation of all required and optional Flysystem methods
- ✅ **Laravel Integration**: Seamless integration with Laravel's Storage facade
 - ✅ **Huawei OBS SDK Integration**: Uses `mubbi/esdk-obs-php` (Huawei OBS SDK compatible fork)
- ✅ **Temporary Credentials**: Support for session tokens (`securityToken`)
- ✅ **Signed URLs**: Generate pre-signed URLs for temporary object access
- ✅ **Public URLs**: Generate public URLs for objects with public read access
- ✅ **Post Signatures**: Create signatures for direct browser uploads to OBS
- ✅ **Object Tagging**: Add and manage metadata tags on OBS objects
- ✅ **Object Restoration**: Restore archived objects from OBS
- ✅ **Credential Refresh**: Update credentials during runtime
- ✅ **Authentication Validation**: Proactive checking of OBS credentials and bucket access
- ✅ **Comprehensive Error Handling**: Clear, actionable error messages
- ✅ **Security-Focused Design**: Private visibility by default, SSL verification
- ✅ **Configuration Validation**: Automatic validation of required configuration parameters
- ✅ **Retry Logic**: Automatic retry with exponential backoff for transient errors
- ✅ **Authentication Caching**: Cache authentication status to improve performance
- ✅ **Logging Support**: Optional operation and error logging
- ✅ **Artisan Commands**: Built-in testing command for connectivity verification
- ✅ **Type Safety**: PHPStan level 8 compliance with strict typing
- ✅ **Code Quality**: Laravel Pint compliance and PSR-12 standards
- ✅ **Performance Optimized**: Built-in timeout protection and infinite loop prevention
- ✅ **Large Dataset Support**: Optimized methods for handling large numbers of files

## Security Features

This package includes robust security features:

- **🔐 Authentication Validation**: Automatic authentication checks before all operations
- **🛡️ Secure Defaults**: Private visibility by default, SSL verification enabled
- **🔑 Temporary Credentials**: Support for security tokens and credential rotation
- **🌐 Signed URLs**: Secure temporary access without exposing credentials
- **📝 Input Sanitization**: Path normalization and validation
- **🚫 Error Handling**: No sensitive data exposure in error messages
- **🔄 Infinite Loop Prevention**: Built-in safety mechanisms to prevent timeouts

## Requirements

- PHP 8.1+
- Laravel 9.0+ (supports Laravel 9, 10.48.29+, 11, and 12)
- Flysystem v3.0+ (primary) with v2.0+ compatibility
- Guzzle v6.3.0+, v7.0+, or v8.0+ (automatic detection)
- Huawei Cloud OBS account and credentials

> **⚠️ Security Notice:** Laravel 10.0.0 to 10.48.28 contains a file validation bypass vulnerability (CVE-2025-27515). This package requires Laravel 10.48.29+ to ensure security. Please upgrade your Laravel installation if you're using an affected version.

## Installation

### Version Compatibility

This package automatically detects and adapts to your installed versions of Guzzle:

- **Guzzle v6**: Uses v6-specific client configurations
- **Guzzle v7**: Uses v7-specific client configurations
- **Guzzle v8**: Uses v8-specific client configurations (with PSR-18 compliance)

For Flysystem, the package uses v3 as the primary interface but maintains compatibility with v2 through dependency constraints. The package will automatically choose the correct HTTP client configuration based on your installed Guzzle version.

### Installation Steps

1. Install the package via Composer:

```bash
composer require mubbi/laravel-flysystem-huawei-obs
```

2. Publish the configuration (optional):

```bash
php artisan vendor:publish --provider="LaravelFlysystemHuaweiObs\HuaweiObsServiceProvider" --tag=huawei-obs-config
```

3. Add your Huawei OBS credentials to your `.env` file:

```env
# Required Configuration
HUAWEI_OBS_ACCESS_KEY_ID=your_access_key_id
HUAWEI_OBS_SECRET_ACCESS_KEY=your_secret_access_key
HUAWEI_OBS_BUCKET=your_bucket_name
HUAWEI_OBS_ENDPOINT=https://obs.cn-north-1.myhuaweicloud.com

# Optional Configuration
HUAWEI_OBS_PREFIX=optional_prefix
HUAWEI_OBS_SECURITY_TOKEN=your_security_token_for_temporary_credentials

# Advanced OBSClient Configuration
HUAWEI_OBS_SIGNATURE=v4
HUAWEI_OBS_PATH_STYLE=false
HUAWEI_OBS_REGION=cn-north-1
HUAWEI_OBS_SSL_CERTIFICATE_AUTHORITY=
HUAWEI_OBS_MAX_RETRY_COUNT=3
HUAWEI_OBS_SOCKET_TIMEOUT=60
HUAWEI_OBS_CHUNK_SIZE=8192
HUAWEI_OBS_EXCEPTION_RESPONSE_MODE=exception
HUAWEI_OBS_IS_CNAME=false

# HTTP Client Configuration
HUAWEI_OBS_TIMEOUT=120
HUAWEI_OBS_CONNECT_TIMEOUT=30
HUAWEI_OBS_VERIFY_SSL=true

# Retry Configuration
HUAWEI_OBS_RETRY_ATTEMPTS=3
HUAWEI_OBS_RETRY_DELAY=1

# Logging Configuration
HUAWEI_OBS_LOGGING_ENABLED=false
HUAWEI_OBS_LOG_OPERATIONS=false
HUAWEI_OBS_LOG_ERRORS=true
```

4. Configure your filesystem in `config/filesystems.php`:

```php
'disks' => [
    'huawei-obs' => [
        'driver' => 'huawei-obs',
        
        // Required Configuration
        'key' => env('HUAWEI_OBS_ACCESS_KEY_ID'),
        'secret' => env('HUAWEI_OBS_SECRET_ACCESS_KEY'),
        'bucket' => env('HUAWEI_OBS_BUCKET'),
        'endpoint' => env('HUAWEI_OBS_ENDPOINT'),
        
        // Optional Configuration
        'prefix' => env('HUAWEI_OBS_PREFIX'),
        'security_token' => env('HUAWEI_OBS_SECURITY_TOKEN'),
        
        // Advanced OBSClient Configuration
        'signature' => env('HUAWEI_OBS_SIGNATURE', 'v4'),
        'path_style' => env('HUAWEI_OBS_PATH_STYLE', false),
        'region' => env('HUAWEI_OBS_REGION'),
        'ssl_verify' => env('HUAWEI_OBS_VERIFY_SSL', true),
        'ssl.certificate_authority' => env('HUAWEI_OBS_SSL_CERTIFICATE_AUTHORITY'),
        'max_retry_count' => env('HUAWEI_OBS_MAX_RETRY_COUNT', 3),
        'timeout' => env('HUAWEI_OBS_TIMEOUT', 120),
        'socket_timeout' => env('HUAWEI_OBS_SOCKET_TIMEOUT', 60),
        'connect_timeout' => env('HUAWEI_OBS_CONNECT_TIMEOUT', 30),
        'chunk_size' => env('HUAWEI_OBS_CHUNK_SIZE', 8192),
        'exception_response_mode' => env('HUAWEI_OBS_EXCEPTION_RESPONSE_MODE', 'exception'),
        'is_cname' => env('HUAWEI_OBS_IS_CNAME', false),
        
        // HTTP Client Configuration
        'http_client' => [
            'timeout' => env('HUAWEI_OBS_TIMEOUT', 120),
            'connect_timeout' => env('HUAWEI_OBS_CONNECT_TIMEOUT', 30),
            'verify' => env('HUAWEI_OBS_VERIFY_SSL', true),
            'proxy' => null,
            'headers' => [],
        ],
        
        // Retry Configuration
        'retry_attempts' => env('HUAWEI_OBS_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('HUAWEI_OBS_RETRY_DELAY', 1),
        
        // Logging Configuration
        'logging_enabled' => env('HUAWEI_OBS_LOGGING_ENABLED', false),
        'log_operations' => env('HUAWEI_OBS_LOG_OPERATIONS', false),
        'log_errors' => env('HUAWEI_OBS_LOG_ERRORS', true),
        
        // Flysystem Configuration
        'visibility' => 'private',
        'throw' => false,
    ],
],
```

## Basic Usage

### Laravel Storage Facade

The package integrates seamlessly with Laravel's Storage facade:

```php
use Illuminate\Support\Facades\Storage;

// Upload a file
Storage::disk('huawei-obs')->put('file.txt', 'Hello World');

// Download a file
$contents = Storage::disk('huawei-obs')->get('file.txt');

// Check if file exists
$exists = Storage::disk('huawei-obs')->exists('file.txt');

// Delete a file
Storage::disk('huawei-obs')->delete('file.txt');

// Get file URL
$url = Storage::disk('huawei-obs')->url('file.txt');

// Get temporary URL
$tempUrl = Storage::disk('huawei-obs')->temporaryUrl('file.txt', now()->addHour());
```

### Direct Adapter Usage

You can also use the adapter directly (Flysystem v3 interface):

```php
use LaravelFlysystemHuaweiObs\LaravelHuaweiObsAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Config;

$adapter = new LaravelHuaweiObsAdapter(
    env('HUAWEI_OBS_ACCESS_KEY_ID'),
    env('HUAWEI_OBS_SECRET_ACCESS_KEY'),
    env('HUAWEI_OBS_BUCKET'),
    env('HUAWEI_OBS_ENDPOINT'),
    env('HUAWEI_OBS_PREFIX'),
);

$filesystem = new Filesystem($adapter);

$filesystem->write('file.txt', 'Hello World', new Config());
$contents = $filesystem->read('file.txt');
```

## Configuration Options

### Required Configuration

These options are required for the adapter to function:

- `key` - Your Huawei OBS Access Key ID
- `secret` - Your Huawei OBS Secret Access Key  
- `bucket` - The OBS bucket name
- `endpoint` - The OBS endpoint URL (e.g., `https://obs.cn-north-1.myhuaweicloud.com`)

### Optional Configuration

- `prefix` - Optional path prefix for all operations
- `security_token` - Security token for temporary credentials

### Advanced OBSClient Configuration

The adapter supports all OBSClient configuration options:

- `signature` - Signature version (`v2`, `v4`, `obs`) - Default: `v4`
- `path_style` - Use path-style URLs instead of virtual-hosted-style - Default: `false`
- `region` - OBS region (e.g., `cn-north-1`) - Optional
- `ssl_verify` - Enable/disable SSL certificate verification - Default: `true`
- `ssl.certificate_authority` - Path to custom CA certificate bundle - Optional
- `max_retry_count` - Maximum number of retry attempts - Default: `3`
- `timeout` - Request timeout in seconds - Default: `120`
- `socket_timeout` - Socket timeout in seconds - Default: `60`
- `connect_timeout` - Connection timeout in seconds - Default: `30`
- `chunk_size` - Chunk size for multipart uploads in bytes - Default: `8192`
- `exception_response_mode` - Exception response mode (`exception`, `response`) - Default: `exception`
- `is_cname` - Whether the endpoint is a CNAME - Default: `false`

### HTTP Client Configuration

- `http_client.timeout` - Request timeout in seconds - Default: `120`
- `http_client.connect_timeout` - Connection timeout in seconds - Default: `30`
- `http_client.verify` - SSL certificate verification - Default: `true`
- `http_client.proxy` - HTTP proxy configuration - Optional
- `http_client.headers` - Additional HTTP headers - Optional

### Retry Configuration

- `retry_attempts` - Number of retry attempts for transient errors - Default: `3`
- `retry_delay` - Base delay between retries in seconds - Default: `1`

### Logging Configuration

- `logging_enabled` - Enable/disable logging - Default: `false`
- `log_operations` - Log successful operations - Default: `false`
- `log_errors` - Log errors - Default: `true`

### Examples

See the `examples/` directory for concise, copy-pasteable snippets:

- `examples/usage.php`: basic write/read with Flysystem
- `examples/url-usage.php`: URL and temporary URL usage
- `examples/advanced-usage.php`: signed URLs, post signatures, tags, restore, optimized listing
- `examples/controller-compatibility.php`: controller-style listing example
- `examples/laravel-compatibility-demo.php`: Storage facade compatibility helpers

## Advanced Features

### Optimized Methods for Large Datasets

For applications with large numbers of files, the package provides optimized methods with built-in timeout protection:

```php
// Get storage statistics with timeout protection
$stats = Storage::disk('huawei-obs')->getStorageStats(10000, 60);
// Returns: ['total_files' => 1234, 'total_directories' => 56, 'total_size_bytes' => 1073741824, ...]

// Get files with limits and timeout
$files = Storage::disk('huawei-obs')->allFilesOptimized(1000, 30);

// Get directories with limits and timeout
$directories = Storage::disk('huawei-obs')->allDirectoriesOptimized(1000, 30);

// List contents with advanced controls
foreach (Storage::disk('huawei-obs')->listContentsOptimized('path', true, 1000, 60) as $item) {
    // Process items with timeout protection
}
```

### Retry Logic and Error Handling

The adapter includes automatic retry logic with exponential backoff for transient errors:

```php
// Configure retry behavior
'huawei-obs' => [
    'retry_attempts' => 3,    // Number of retry attempts
    'retry_delay' => 1,       // Base delay in seconds
    // ... other config
],
```

The retry logic will automatically retry on transient errors but will not retry on:
- Authentication errors (`AccessDenied`, `InvalidAccessKeyId`, `SignatureDoesNotMatch`)
- Configuration errors (`NoSuchBucket`)

### Authentication Caching

Authentication status is cached for 5 minutes to improve performance:

```php
// Refresh authentication cache manually
$adapter->refreshAuthentication();

// Refresh credentials (clears cache automatically)
$adapter->refreshCredentials('new-key', 'new-secret', 'new-token');
```

### Logging

Enable operation and error logging:

```php
'huawei-obs' => [
    'logging_enabled' => true,
    'log_operations' => true,  // Log successful operations
    'log_errors' => true,      // Log errors
    // ... other config
],
```

### Laravel Storage Facade Compatibility

This package provides full compatibility with Laravel's Storage facade. All the standard Laravel Storage methods are supported:

#### Directory Listing
```php
// List files in a directory (non-recursive)
$files = Storage::disk('huawei-obs')->files('uploads');

// List directories in a directory (non-recursive)
$directories = Storage::disk('huawei-obs')->directories('uploads');

// List all files and directories (recursive)
$allFiles = Storage::disk('huawei-obs')->allFiles();
$allDirectories = Storage::disk('huawei-obs')->allDirectories();
```

#### File Information
```php
// Check if file exists
$exists = Storage::disk('huawei-obs')->exists('file.txt');

// Get file size
$size = Storage::disk('huawei-obs')->size('file.txt');

// Get last modified timestamp
$modified = Storage::disk('huawei-obs')->lastModified('file.txt');

// Get MIME type
$mimeType = Storage::disk('huawei-obs')->mimeType('file.txt');

// Get file visibility
$visibility = Storage::disk('huawei-obs')->visibility('file.txt');
```

#### Complete Example
```php
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
        'visibility' => Storage::disk('huawei-obs')->visibility($file)
    ];
}

return response()->json([
    'success' => true,
    'files' => $fileDetails,
    'directories' => $directories,
    'total_files' => count($files),
    'total_directories' => count($directories)
]);
```

Logged information includes:
- Operation name and duration
- File path and bucket
- Error details and codes
- Additional context (tags, headers, etc.)

### Configuration Validation

The service provider automatically validates required configuration:

```php
// Missing required fields will throw clear errors
'huawei-obs' => [
    'key' => '', // ❌ Will throw: "Missing required configuration: key"
    // ... other config
],
```

### Custom Exceptions

The package provides custom exceptions for better error handling:

```php
use LaravelFlysystemHuaweiObs\Exceptions\UnableToCreateSignedUrl;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToSetObjectTags;

try {
    $signedUrl = $adapter->createSignedUrl('file.txt');
} catch (UnableToCreateSignedUrl $e) {
    // Handle signed URL creation errors
}

try {
    $adapter->setObjectTags('file.txt', ['tag' => 'value']);
} catch (UnableToSetObjectTags $e) {
    // Handle object tagging errors
}
```

### Authentication & Security

The adapter automatically validates authentication before operations and provides clear error messages:

```php
try {
    Storage::disk('huawei-obs')->exists('file.txt');
} catch (\RuntimeException $e) {
    // Clear authentication error message
    // "Authentication failed. Please check your Huawei OBS credentials..."
}
```

### Temporary Credentials (Security Token)

For applications using temporary credentials (like AWS STS or similar services):

```php
// Configure with security token
$adapter = new \LaravelFlysystemHuaweiObs\HuaweiObsAdapter(
    'access_key_id',
    'secret_access_key',
    'bucket_name',
    'endpoint',
    null, // prefix
    null, // http client
    'security_token_here'
);

// Or refresh credentials during runtime
$adapter->refreshCredentials('new_access_key', 'new_secret_key', 'new_security_token');
```

### URL Handling

The adapter supports both public URLs and signed URLs. For Laravel's `Storage` facade:

#### Public and Private URLs

The `url()` method automatically handles both public and private objects:

```php
// For public objects, returns a direct URL
$publicUrl = Storage::disk('huawei-obs')->url('public-file.txt');
// Returns: https://obs.example.com/bucket/public-file.txt

// For private objects, automatically returns a signed URL (1-hour expiration)
$privateUrl = Storage::disk('huawei-obs')->url('private-file.txt');
// Returns: https://obs.example.com/bucket/private-file.txt?signature=...
```

#### Temporary URLs

Generate temporary URLs with custom expiration times:

```php
// Generate a temporary URL that expires in 2 hours
$tempUrl = Storage::disk('huawei-obs')->temporaryUrl(
    'file.txt',
    now()->addHours(2)
);

// Generate a temporary URL for uploads (PUT method)
$uploadUrl = Storage::disk('huawei-obs')->temporaryUploadUrl(
    'file.txt',
    now()->addHour()
);
```

### Object Tagging

Add and manage metadata tags on OBS objects:

```php
// Set tags on an object
Storage::disk('huawei-obs')->setObjectTags('file.txt', [
    'category' => 'images',
    'processed' => 'true',
    'user_id' => '123'
]);

// Get tags from an object
$tags = Storage::disk('huawei-obs')->getObjectTags('file.txt');

// Delete tags from an object
Storage::disk('huawei-obs')->deleteObjectTags('file.txt');
```

### Post Signatures

Create signatures for direct browser uploads to OBS:

```php
// Generate a post signature for direct upload
$signature = Storage::disk('huawei-obs')->createPostSignature('uploads/file.txt', [
    'success_action_status' => '201',
    'x-amz-meta-category' => 'images'
], 3600); // 1 hour expiration

// Use the signature in an HTML form
echo '<form action="' . $signature['url'] . '" method="post" enctype="multipart/form-data">';
foreach ($signature['fields'] as $key => $value) {
    echo '<input type="hidden" name="' . $key . '" value="' . $value . '">';
}
echo '<input type="file" name="file">';
echo '<input type="submit" value="Upload">';
echo '</form>';
```

### Object Restoration

Restore archived objects from OBS:

```php
// Restore an archived object (default 1 day)
Storage::disk('huawei-obs')->restoreObject('archived-file.txt');

// Restore with custom restoration period (7 days)
Storage::disk('huawei-obs')->restoreObject('archived-file.txt', 7);
```

## Testing

### Artisan Command

The package includes an Artisan command for testing connectivity and features:

```bash
php artisan huawei-obs:test
```

This command will:
- Validate credentials and bucket access
- Test reads/writes, signed URLs, post signatures, and tagging
- Output a concise pass/fail report

### Manual Testing

You can also test the adapter manually:

```php
use LaravelFlysystemHuaweiObs\HuaweiObsAdapter;

$adapter = new HuaweiObsAdapter(
    'your_access_key_id',
    'your_secret_access_key',
    'your_bucket_name',
    'https://obs.cn-north-1.myhuaweicloud.com'
);

// Test basic operations
$adapter->write('test.txt', 'Hello World', new \League\Flysystem\Config());
$contents = $adapter->read('test.txt');
$adapter->delete('test.txt');
```

## Error Handling

The package provides comprehensive error handling with clear, actionable error messages:

### Common Error Scenarios

```php
try {
    Storage::disk('huawei-obs')->put('file.txt', 'content');
} catch (\RuntimeException $e) {
    // Authentication errors
    if (str_contains($e->getMessage(), 'Authentication failed')) {
        // Check your credentials
    }
    
    // Bucket errors
    if (str_contains($e->getMessage(), 'NoSuchBucket')) {
        // Check your bucket name
    }
    
    // Permission errors
    if (str_contains($e->getMessage(), 'AccessDenied')) {
        // Check your IAM permissions
    }
}
```

### Custom Exceptions

The package provides specific exceptions for different error types used by advanced features:

```php
use LaravelFlysystemHuaweiObs\Exceptions\UnableToCreateSignedUrl;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToSetObjectTags;

try {
    $url = Storage::disk('huawei-obs')->temporaryUrl('file.txt', now()->addHour());
} catch (UnableToCreateSignedUrl $e) {
    // Handle signed URL creation errors
}

try {
    Storage::disk('huawei-obs')->setObjectTags('file.txt', ['tag' => 'value']);
} catch (UnableToSetObjectTags $e) {
    // Handle object tagging errors
}
```

## Performance Considerations

### Large Dataset Handling

For applications with large numbers of files, use the optimized methods:

```php
// Instead of allFiles() which might timeout
$allFiles = Storage::disk('huawei-obs')->allFiles();

// Use optimized method with limits
$files = Storage::disk('huawei-obs')->allFilesOptimized(10000, 60);
```

### Caching

Consider caching frequently accessed data:

```php
// Cache file listings
$files = Cache::remember('huawei-obs-files', 300, function () {
    return Storage::disk('huawei-obs')->files('uploads');
});
```

### Batch Operations

For multiple operations, consider batching:

```php
// Instead of multiple individual calls
foreach ($files as $file) {
    Storage::disk('huawei-obs')->delete($file);
}

// Consider using background jobs for large batches
```

## Troubleshooting

### Common Issues

1. **Authentication Errors**
   - Verify your access key and secret key
   - Check if your security token is still valid
   - Ensure your credentials have the necessary permissions

2. **Bucket Access Errors**
   - Verify the bucket name is correct
   - Check if the bucket exists in the specified region
   - Ensure your credentials have access to the bucket

3. **Timeout Errors**
   - Use optimized methods for large datasets
   - Increase timeout values in configuration
   - Consider using background processing for large operations

4. **SSL Certificate Errors**
   - Verify your endpoint URL is correct
   - Check if SSL verification is required in your environment
   - Consider disabling SSL verification for testing (not recommended for production)

### Debug Mode

Enable debug mode to get more detailed error information:

```php
'huawei-obs' => [
    'logging_enabled' => true,
    'log_operations' => true,
    'log_errors' => true,
    // ... other config
],
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

### Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Run tests: `composer test`
4. Static analysis: `composer phpstan`
5. Lint/format: `composer pint`

### Code Quality

This package follows strict code quality standards:

- **PHPStan Level 8**: Maximum type safety
- **Laravel Pint**: PSR-12 coding standards
- **PHPUnit**: Comprehensive test coverage
- **GitHub Actions**: Automated CI/CD pipeline

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

- **Documentation**: [GitHub Wiki](https://github.com/mubbi/laravel-flysystem-huawei-obs/wiki)
- **Issues**: [GitHub Issues](https://github.com/mubbi/laravel-flysystem-huawei-obs/issues)
- **Discussions**: [GitHub Discussions](https://github.com/mubbi/laravel-flysystem-huawei-obs/discussions)

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Credits

- **Author**: [Mubbasher Ahmed](https://mubbi.me)
- **Maintainer**: [Mubbasher Ahmed](https://mubbi.me)
- **License**: [MIT](LICENSE)

---

**Made with ❤️ by [Mubbasher Ahmed](https://mubbi.me)** 
