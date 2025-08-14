<?php

declare(strict_types=1);

/**
 * Laravel Huawei OBS Adapter
 *
 * A Laravel-specific adapter that provides Laravel Storage facade compatibility
 * by implementing the expected method signatures while using the base adapter
 * for underlying operations.
 *
 * @author  Mubbasher Ahmed <hello@mubbi.me>
 *
 * @link    https://mubbi.me
 *
 * @license MIT
 */

namespace LaravelFlysystemHuaweiObs;

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;

class LaravelHuaweiObsAdapter implements FilesystemAdapter
{
    private HuaweiObsAdapter $adapter;

    public function __construct(
        string $accessKeyId,
        string $secretAccessKey,
        string $bucket,
        string $endpoint,
        ?string $prefix = null,
        ?\GuzzleHttp\ClientInterface $httpClient = null,
        ?string $securityToken = null,
        int $retryAttempts = 3,
        int $retryDelay = 1,
        bool $loggingEnabled = false,
        bool $logOperations = false,
        bool $logErrors = true,
        bool $sslVerify = true,
        ?string $signature = null,
        ?bool $pathStyle = null,
        ?string $region = null,
        ?string $sslCertificateAuthority = null,
        ?int $maxRetryCount = null,
        ?int $timeout = null,
        ?int $socketTimeout = null,
        ?int $connectTimeout = null,
        ?int $chunkSize = null,
        ?string $exceptionResponseMode = null,
        ?bool $isCname = null
    ) {
        $this->adapter = new HuaweiObsAdapter(
            $accessKeyId,
            $secretAccessKey,
            $bucket,
            $endpoint,
            $prefix,
            $httpClient,
            $securityToken,
            $retryAttempts,
            $retryDelay,
            $loggingEnabled,
            $logOperations,
            $logErrors,
            $sslVerify,
            $signature,
            $pathStyle,
            $region,
            $sslCertificateAuthority,
            $maxRetryCount,
            $timeout,
            $socketTimeout,
            $connectTimeout,
            $chunkSize,
            $exceptionResponseMode,
            $isCname
        );
    }

    // Delegate all Flysystem interface methods to the base adapter
    public function fileExists(string $path): bool
    {
        return $this->adapter->fileExists($path);
    }

    public function directoryExists(string $path): bool
    {
        return $this->adapter->directoryExists($path);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->adapter->write($path, $contents, $config);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->adapter->writeStream($path, $contents, $config);
    }

    public function read(string $path): string
    {
        return $this->adapter->read($path);
    }

    public function readStream(string $path)
    {
        return $this->adapter->readStream($path);
    }

    public function delete(string $path): void
    {
        $this->adapter->delete($path);
    }

    public function deleteDirectory(string $path): void
    {
        $this->adapter->deleteDirectory($path);
    }

    public function createDirectory(string $path, Config $config): void
    {
        $this->adapter->createDirectory($path, $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $this->adapter->setVisibility($path, $visibility);
    }

    public function visibility(string $path): FileAttributes
    {
        return $this->adapter->visibility($path);
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->adapter->mimeType($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->adapter->lastModified($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->adapter->fileSize($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        return $this->adapter->listContents($path, $deep);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->adapter->move($source, $destination, $config);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->adapter->copy($source, $destination, $config);
    }

    // Laravel Storage facade compatibility methods
    /**
     * Get files in a directory (non-recursive)
     *
     * @param  string  $directory  The directory path
     * @return array<string>
     */
    public function files(string $directory = ''): array
    {
        $files = [];
        foreach ($this->listContents($directory, false) as $item) {
            if ($item instanceof FileAttributes) {
                $files[] = $item->path();
            }
        }

        return $files;
    }

    /**
     * Get directories in a directory (non-recursive)
     *
     * @param  string  $directory  The directory path
     * @return array<string>
     */
    public function directories(string $directory = ''): array
    {
        $directories = [];
        foreach ($this->listContents($directory, false) as $item) {
            if ($item instanceof DirectoryAttributes) {
                $directories[] = $item->path();
            }
        }

        return $directories;
    }

    /**
     * Check if a file exists (Laravel Storage facade compatibility)
     *
     * @param  string  $path  The file path
     */
    public function exists(string $path): bool
    {
        return $this->fileExists($path);
    }

    /**
     * Get file size (Laravel Storage facade compatibility)
     *
     * @param  string  $path  The file path
     */
    public function size(string $path): int
    {
        $attributes = $this->fileSize($path);

        return $attributes->fileSize() ?? 0;
    }

    /**
     * Get last modified timestamp (Laravel Storage facade compatibility)
     *
     * @param  string  $path  The file path
     */
    public function getLastModified(string $path): int
    {
        $attributes = $this->lastModified($path);

        return $attributes->lastModified() ?? time();
    }

    /**
     * Get MIME type (Laravel Storage facade compatibility)
     *
     * @param  string  $path  The file path
     */
    public function getMimeType(string $path): string
    {
        $attributes = $this->mimeType($path);

        return $attributes->mimeType() ?? 'application/octet-stream';
    }

    /**
     * Get visibility (Laravel Storage facade compatibility)
     *
     * @param  string  $path  The file path
     */
    public function getVisibility(string $path): string
    {
        $attributes = $this->visibility($path);

        return $attributes->visibility() ?? 'private';
    }

    // Additional Laravel compatibility methods
    /**
     * Get the URL for the file at the given path.
     *
     * @param  string  $path  The file path
     * @return string The URL
     */
    public function url(string $path): string
    {
        return $this->adapter->url($path);
    }

    /**
     * Get a temporary URL for the file at the given path.
     *
     * @param  string  $path  The file path
     * @param  \DateTimeInterface  $expiration  The expiration time
     * @param  array<string, mixed>  $options  Additional options
     * @return string The temporary URL
     */
    public function getTemporaryUrl(string $path, \DateTimeInterface $expiration, array $options = []): string
    {
        return $this->adapter->getTemporaryUrl($path, $expiration, $options);
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param  string  $path  The file path
     * @return string The URL
     */
    public function getUrl(string $path): string
    {
        return $this->adapter->getUrl($path);
    }

    /**
     * Get a temporary upload URL for the file at the given path.
     *
     * @param  string  $path  The file path
     * @param  \DateTimeInterface  $expiration  The expiration time
     * @param  array<string, mixed>  $options  Additional options
     * @return string The temporary upload URL
     */
    public function temporaryUploadUrl(string $path, \DateTimeInterface $expiration, array $options = []): string
    {
        return $this->adapter->temporaryUploadUrl($path, $expiration, $options);
    }

    // Advanced Huawei OBS features passthroughs
    /**
     * @param  array<string, string>  $headers
     */
    public function createSignedUrl(string $path, string $method = 'GET', int $expires = 3600, array $headers = []): string
    {
        return $this->adapter->createSignedUrl($path, $method, $expires, $headers);
    }

    /**
     * @param  array<int, array<string, mixed>>  $conditions
     * @return array<string, mixed>
     */
    public function createPostSignature(string $path, array $conditions = [], int $expires = 3600): array
    {
        return $this->adapter->createPostSignature($path, $conditions, $expires);
    }

    /**
     * @param  array<string, string>  $tags
     */
    public function setObjectTags(string $path, array $tags): void
    {
        $this->adapter->setObjectTags($path, $tags);
    }

    /**
     * @return array<string, string>
     */
    public function getObjectTags(string $path): array
    {
        return $this->adapter->getObjectTags($path);
    }

    public function deleteObjectTags(string $path): void
    {
        $this->adapter->deleteObjectTags($path);
    }

    public function restoreObject(string $path, int $days = 1): void
    {
        $this->adapter->restoreObject($path, $days);
    }

    /**
     * Get all files in the bucket (recursive)
     *
     * @return array<string>
     */
    public function allFiles(): array
    {
        return $this->adapter->allFiles();
    }

    /**
     * Get all directories in the bucket (recursive)
     *
     * @return array<string>
     */
    public function allDirectories(): array
    {
        return $this->adapter->allDirectories();
    }

    /**
     * Get all files in the bucket (recursive) with optimized performance
     *
     * @param  int  $maxKeys  Maximum number of keys to retrieve (0 for unlimited)
     * @param  int  $timeout  Timeout in seconds for the operation
     * @return array<string>
     */
    public function allFilesOptimized(int $maxKeys = 0, int $timeout = 60): array
    {
        return $this->adapter->allFilesOptimized($maxKeys, $timeout);
    }

    /**
     * Get all directories in the bucket (recursive) with optimized performance
     *
     * @param  int  $maxKeys  Maximum number of keys to retrieve (0 for unlimited)
     * @param  int  $timeout  Timeout in seconds for the operation
     * @return array<string>
     */
    public function allDirectoriesOptimized(int $maxKeys = 0, int $timeout = 60): array
    {
        return $this->adapter->allDirectoriesOptimized($maxKeys, $timeout);
    }

    /**
     * Get storage statistics with optimized performance
     *
     * @param  int  $maxFiles  Maximum number of files to process (0 for unlimited)
     * @param  int  $timeout  Timeout in seconds for the operation
     * @return array<string, mixed>
     */
    public function getStorageStats(int $maxFiles = 0, int $timeout = 60): array
    {
        return $this->adapter->getStorageStats($maxFiles, $timeout);
    }

    /**
     * Optimized listContents with better timeout and pagination handling
     *
     * @param  string  $path  The path to list
     * @param  bool  $deep  Whether to list recursively
     * @param  int  $maxKeys  Maximum number of keys to retrieve (0 for unlimited)
     * @param  int  $timeout  Timeout in seconds for the operation
     * @return iterable<\League\Flysystem\FileAttributes|\League\Flysystem\DirectoryAttributes>
     */
    public function listContentsOptimized(string $path, bool $deep, int $maxKeys = 0, int $timeout = 60): iterable
    {
        return $this->adapter->listContentsOptimized($path, $deep, $maxKeys, $timeout);
    }
}
