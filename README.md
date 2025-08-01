# Laravel Flysystem Huawei OBS Adapter

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mubbi/laravel-flysystem-huawei-obs.svg?style=flat-square)](https://packagist.org/packages/mubbi/laravel-flysystem-huawei-obs)
[![codecov](https://codecov.io/github/mubbi/laravel-flysystem-huawei-obs/graph/badge.svg?token=UQRPIGQORG)](https://codecov.io/github/mubbi/laravel-flysystem-huawei-obs)
[![Total Downloads](https://img.shields.io/packagist/dt/mubbi/laravel-flysystem-huawei-obs.svg?style=flat-square)](https://packagist.org/packages/mubbi/laravel-flysystem-huawei-obs)
[![GitHub CI](https://img.shields.io/github/actions/workflow/status/mubbi/laravel-flysystem-huawei-obs/ci.yml?branch=main&label=CI&style=flat-square)](https://github.com/mubbi/laravel-flysystem-huawei-obs/actions/workflows/ci.yml)
[![License](https://img.shields.io/github/license/mubbi/laravel-flysystem-huawei-obs.svg?style=flat-square)](https://github.com/mubbi/laravel-flysystem-huawei-obs/blob/main/LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/mubbi/laravel-flysystem-huawei-obs.svg?style=flat-square)](https://packagist.org/packages/mubbi/laravel-flysystem-huawei-obs)
[![Laravel Version](https://img.shields.io/badge/Laravel-9%2B-red.svg?style=flat-square)](https://laravel.com)
[![Flysystem Version](https://img.shields.io/badge/Flysystem-v2%20%7C%20v3-blue.svg?style=flat-square)](https://flysystem.thephpleague.com/)
[![Guzzle Version](https://img.shields.io/badge/Guzzle-v6%20%7C%20v7%20%7C%20v8-green.svg?style=flat-square)](https://docs.guzzlephp.org/)

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
- ✅ **Huawei OBS SDK Integration**: Uses the official `obs/esdk-obs-php` SDK
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

## Security Features

This package includes robust security features:

- **🔐 Authentication Validation**: Automatic authentication checks before all operations
- **🛡️ Secure Defaults**: Private visibility by default, SSL verification enabled
- **🔑 Temporary Credentials**: Support for security tokens and credential rotation
- **🌐 Signed URLs**: Secure temporary access without exposing credentials
- **📝 Input Sanitization**: Path normalization and validation
- **🚫 Error Handling**: No sensitive data exposure in error messages

## Requirements

- PHP 8.1+
- Laravel 9.0+ (supports Laravel 9, 10, 11, and 12)
- Flysystem v3.0+ (primary) with v2.0+ compatibility
- Guzzle v6.3.0+, v7.0+, or v8.0+ (automatic detection)
- Huawei Cloud OBS account and credentials

## Installation

### Version Compatibility

This package automatically detects and adapts to your installed versions of Guzzle:

- **Guzzle v6**: Uses v6-specific client configurations
- **Guzzle v7/v8**: Uses modern client configurations

For Flysystem, the package uses v3 as the primary interface but maintains compatibility with v2 through dependency constraints. The package will automatically choose the correct HTTP client configuration based on your installed Guzzle version.

### Installation Steps

1. Install the package via Composer:

```bash
composer require mubbi/laravel-flysystem-huawei-obs
```

2. Publish the configuration (optional):

```bash
php artisan vendor:publish --provider="LaravelFlysystemHuaweiObs\HuaweiObsServiceProvider"
```

3. Add your Huawei OBS credentials to your `.env` file:

```env
HUAWEI_OBS_ACCESS_KEY_ID=your_access_key_id
HUAWEI_OBS_SECRET_ACCESS_KEY=your_secret_access_key
HUAWEI_OBS_BUCKET=your_bucket_name
HUAWEI_OBS_ENDPOINT=https://obs.cn-north-1.myhuaweicloud.com
HUAWEI_OBS_REGION=cn-north-1
HUAWEI_OBS_PREFIX=optional_prefix
HUAWEI_OBS_SECURITY_TOKEN=your_security_token_for_temporary_credentials
```

4. Configure your filesystem in `config/filesystems.php`:

```php
'disks' => [
    // ... other disks

    'huawei-obs' => [
        'driver' => 'huawei-obs',
        'key' => env('HUAWEI_OBS_ACCESS_KEY_ID'),
        'secret' => env('HUAWEI_OBS_SECRET_ACCESS_KEY'),
        'bucket' => env('HUAWEI_OBS_BUCKET'),
        'endpoint' => env('HUAWEI_OBS_ENDPOINT'),
        'region' => env('HUAWEI_OBS_REGION'),
        'prefix' => env('HUAWEI_OBS_PREFIX'),
        'security_token' => env('HUAWEI_OBS_SECURITY_TOKEN'),
        'visibility' => 'public',
        'throw' => false,
        'http_client' => [
            'timeout' => 30,
            'connect_timeout' => 10,
            'verify' => true,
            'proxy' => null,
            'headers' => [],
        ],
    ],
],
```

## Basic Usage

### File Operations

```php
use Illuminate\Support\Facades\Storage;

// Upload a file
Storage::disk('huawei-obs')->put('file.txt', 'Hello World');

// Download a file
$content = Storage::disk('huawei-obs')->get('file.txt');

// Check if file exists
if (Storage::disk('huawei-obs')->exists('file.txt')) {
    // File exists
}

// Delete a file
Storage::disk('huawei-obs')->delete('file.txt');

// Copy a file
Storage::disk('huawei-obs')->copy('source.txt', 'destination.txt');

// Move a file
Storage::disk('huawei-obs')->move('old.txt', 'new.txt');

// Get file URL (only for public objects)
$url = Storage::disk('huawei-obs')->url('file.txt');

// For private objects, use signed URLs instead
$adapter = Storage::disk('huawei-obs')->getAdapter();
$signedUrl = $adapter->createSignedUrl('private-file.txt', 'GET', 3600);
```

### Directory Operations

```php
// Create a directory
Storage::disk('huawei-obs')->makeDirectory('uploads');

// List directory contents
$files = Storage::disk('huawei-obs')->files('uploads');
$directories = Storage::disk('huawei-obs')->directories('uploads');

// List all files and directories (recursive)
$allFiles = Storage::disk('huawei-obs')->allFiles();
$allDirectories = Storage::disk('huawei-obs')->allDirectories();

// Delete a directory and all its contents
Storage::disk('huawei-obs')->deleteDirectory('uploads');
```

### File Metadata

```php
// Get file size
$size = Storage::disk('huawei-obs')->size('file.txt');

// Get last modified time
$modified = Storage::disk('huawei-obs')->lastModified('file.txt');

// Get MIME type
$mimeType = Storage::disk('huawei-obs')->mimeType('file.txt');

// Set file visibility
Storage::disk('huawei-obs')->setVisibility('file.txt', 'public');

// Get file visibility
$visibility = Storage::disk('huawei-obs')->visibility('file.txt');

// Alternative method for getting visibility
$visibility = Storage::disk('huawei-obs')->getVisibility('file.txt');
```

## Advanced Features

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

The adapter supports both public URLs and signed URLs:

#### Public URLs

For objects with public read access, you can generate direct URLs:

```php
// Get public URL (only works for public objects)
$url = Storage::disk('huawei-obs')->url('public-file.txt');

// For private objects, this will throw an exception
try {
    $url = Storage::disk('huawei-obs')->url('private-file.txt');
} catch (\RuntimeException $e) {
    // Handle private object error
    echo $e->getMessage(); // "This driver does not support retrieving URLs for private objects. Use createSignedUrl() for temporary access."
}
```

#### Signed URLs

Create temporary URLs for direct access to objects (works for both public and private objects):

```php
$adapter = Storage::disk('huawei-obs')->getAdapter();

// Create a signed URL for GET access (1 hour)
$signedUrl = $adapter->createSignedUrl('file.txt', 'GET', 3600);

// Create a signed URL for PUT access with custom headers
$signedUrl = $adapter->createSignedUrl('file.txt', 'PUT', 7200, [
    'Content-Type' => 'text/plain'
]);
```

### Post Signatures for Direct Uploads

Generate signatures for direct browser uploads:

```php
$adapter = Storage::disk('huawei-obs')->getAdapter();

// Create post signature for direct upload
$signature = $adapter->createPostSignature('uploads/file.txt', [
    ['starts-with', '$key', 'uploads/'],
    ['content-length-range', 0, 10485760], // 10MB max
], 3600);

// Use in HTML form
echo '<form action="' . $signature['url'] . '" method="post" enctype="multipart/form-data">';
echo '<input type="hidden" name="policy" value="' . $signature['Policy'] . '">';
echo '<input type="hidden" name="signature" value="' . $signature['Signature'] . '">';
echo '<input type="file" name="file">';
echo '<input type="submit" value="Upload">';
echo '</form>';
```

### Object Tagging

Add metadata tags to objects:

```php
$adapter = Storage::disk('huawei-obs')->getAdapter();

// Set object tags
$adapter->setObjectTags('file.txt', [
    'environment' => 'production',
    'type' => 'image',
    'user_id' => '12345'
]);

// Get object tags
$tags = $adapter->getObjectTags('file.txt');
// Returns: ['environment' => 'production', 'type' => 'image', 'user_id' => '12345']

// Delete object tags
$adapter->deleteObjectTags('file.txt');
```

### Object Restoration

Restore archived objects:

```php
$adapter = Storage::disk('huawei-obs')->getAdapter();

// Restore an archived object for 7 days
$adapter->restoreObject('archived-file.txt', 7);
```

### Multi-Tenant Support

Use prefixes to separate different tenants or environments:

```php
// Configure with prefix
'disks' => [
    'huawei-obs-tenant1' => [
        'driver' => 'huawei-obs',
        'key' => env('HUAWEI_OBS_ACCESS_KEY_ID'),
        'secret' => env('HUAWEI_OBS_SECRET_ACCESS_KEY'),
        'bucket' => env('HUAWEI_OBS_BUCKET'),
        'endpoint' => env('HUAWEI_OBS_ENDPOINT'),
        'prefix' => 'tenant1',
        // ... other config
    ],
    'huawei-obs-tenant2' => [
        'driver' => 'huawei-obs',
        'key' => env('HUAWEI_OBS_ACCESS_KEY_ID'),
        'secret' => env('HUAWEI_OBS_SECRET_ACCESS_KEY'),
        'bucket' => env('HUAWEI_OBS_BUCKET'),
        'endpoint' => env('HUAWEI_OBS_ENDPOINT'),
        'prefix' => 'tenant2',
        // ... other config
    ],
],
```

### Custom HTTP Client Configuration

Configure custom HTTP client settings:

```php
'disks' => [
    'huawei-obs' => [
        'driver' => 'huawei-obs',
        // ... other config
        'http_client' => [
            'timeout' => 60,
            'connect_timeout' => 20,
            'verify' => false, // Disable SSL verification for testing
            'proxy' => 'http://proxy.example.com:8080',
            'headers' => [
                'User-Agent' => 'MyApp/1.0',
                'X-Custom-Header' => 'value',
            ],
        ],
    ],
],
```

## Error Handling

The adapter provides comprehensive error handling with clear, actionable messages:

```php
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UnableToDeleteFile;

try {
    Storage::disk('huawei-obs')->get('nonexistent.txt');
} catch (UnableToReadFile $e) {
    // Handle read error
}

try {
    Storage::disk('huawei-obs')->put('file.txt', 'content');
} catch (UnableToWriteFile $e) {
    // Handle write error
}

try {
    Storage::disk('huawei-obs')->exists('file.txt');
} catch (\RuntimeException $e) {
    // Handle authentication or configuration errors
    // Clear error messages like:
    // "Authentication failed. Please check your Huawei OBS credentials..."
    // "Bucket 'my-bucket' does not exist or you don't have access to it..."
}
```

## Security Best Practices

### 1. **Credential Management**
```bash
# ❌ Never commit credentials to version control
HUAWEI_OBS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
HUAWEI_OBS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY

# ✅ Use environment variables
HUAWEI_OBS_ACCESS_KEY_ID=${OBS_ACCESS_KEY}
HUAWEI_OBS_SECRET_ACCESS_KEY=${OBS_SECRET_KEY}
```

### 2. **Use Temporary Credentials**
```php
// Use security tokens for temporary access
$adapter = new HuaweiObsAdapter(
    'access_key_id',
    'secret_access_key',
    'bucket_name',
    'endpoint',
    null,
    null,
    'security_token_here' // Temporary credentials
);
```

### 3. **Use Signed URLs Instead of Public Objects**
```php
// Create temporary access URLs instead of making objects public
$signedUrl = $adapter->createSignedUrl('sensitive-file.pdf', 'GET', 3600);
```

### 4. **Enable SSL Verification**
```php
$httpClient = new \GuzzleHttp\Client([
    'verify' => true, // Enable SSL verification
    'timeout' => 30,
    'connect_timeout' => 10,
]);
```

## Testing

### Artisan Command

The package includes an Artisan command to test your Huawei OBS configuration:

```bash
# Test all operations
php artisan huawei-obs:test

# Test specific operations
php artisan huawei-obs:test --write-test
php artisan huawei-obs:test --read-test
php artisan huawei-obs:test --delete-test

# Test a specific disk
php artisan huawei-obs:test --disk=my-huawei-obs-disk
```

The command will test:
- ✅ Authentication
- ✅ Write operations (including signed URLs and object tagging)
- ✅ Read operations (including metadata retrieval)
- ✅ Delete operations
- ✅ Advanced features (signed URLs, post signatures, object tagging)

### Unit Tests

Run the test suite:

```bash
composer test
```

Run with coverage:

```bash
composer test -- --coverage
```

## Documentation

- **[Security Policy](SECURITY.md)** - Security guidelines and vulnerability reporting
- **[Contributing Guide](.github/CONTRIBUTING.md)** - How to contribute to the project
- **[Code of Conduct](CODE_OF_CONDUCT.md)** - Community guidelines
- **[Changelog](CHANGELOG.md)** - Version history and changes
- **[Advanced Examples](examples/advanced-usage.php)** - Comprehensive usage examples

## Support

- **GitHub Issues**: [Report bugs or request features](https://github.com/mubbi/laravel-flysystem-huawei-obs/issues)
- **Security Issues**: [hello@mubbi.me](mailto:hello@mubbi.me)
- **Documentation**: [README.md](README.md)
- **Author**: [Mubbasher Ahmed](https://mubbi.me) - [hello@mubbi.me](mailto:hello@mubbi.me)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently. 