<?php

declare(strict_types=1);

/**
 * Laravel Flysystem Huawei OBS Adapter
 *
 * A Laravel Flysystem v3 adapter for Huawei Object Storage Service (OBS).
 * This package provides seamless integration between Laravel's filesystem
 * abstraction and Huawei Cloud OBS.
 *
 * @author  Mubbasher Ahmed <hello@mubbi.me>
 *
 * @link    https://mubbi.me
 *
 * @license MIT
 */

namespace LaravelFlysystemHuaweiObs;

use GuzzleHttp\Client;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToCreatePostSignature;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToCreateSignedUrl;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToDeleteObjectTags;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToGetObjectTags;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToRestoreObject;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToSetObjectTags;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use Obs\ObsClient;
use Obs\ObsException;

class HuaweiObsAdapter implements FilesystemAdapter
{
    private ObsClient $client;

    private string $bucket;

    private ?string $prefix;

    private ?bool $authenticated = null;

    private ?float $authCacheExpiry = null;

    private int $retryAttempts;

    private int $retryDelay;

    private bool $loggingEnabled;

    private bool $logOperations;

    private bool $logErrors;

    /**
     * Check if the client is properly authenticated
     *
     * @throws \RuntimeException If authentication fails
     */
    private function checkAuthentication(): void
    {
        // Check if authentication is cached and still valid
        if ($this->authenticated === true && $this->authCacheExpiry !== null && time() < $this->authCacheExpiry) {
            return;
        }

        $this->withRetry(function () {
            try {
                // Try to access bucket metadata to verify authentication
                $this->client->headBucket(['Bucket' => $this->bucket]);
                $this->authenticated = true;
                $this->authCacheExpiry = time() + 300; // Cache for 5 minutes
            } catch (ObsException $e) {
                $this->authenticated = false;
                $errorCode = $e->getExceptionCode();

                if ($errorCode === 'AccessDenied' || $errorCode === 'InvalidAccessKeyId' || $errorCode === 'SignatureDoesNotMatch') {
                    throw new \RuntimeException(
                        'Authentication failed. Please check your Huawei OBS credentials (Access Key ID, Secret Access Key, and Security Token if using temporary credentials). Error: '.$e->getMessage(),
                        0,
                        $e
                    );
                }

                if ($errorCode === 'NoSuchBucket') {
                    throw new \RuntimeException(
                        "Bucket '{$this->bucket}' does not exist or you don't have access to it. Please check your bucket configuration.",
                        0,
                        $e
                    );
                }

                // Re-throw other OBS exceptions
                throw $e;
            }
        });
    }

    /**
     * Execute an operation with retry logic
     *
     * @param  callable  $operation  The operation to execute
     * @return mixed The result of the operation
     *
     * @throws \Exception If the operation fails after all retry attempts
     */
    private function withRetry(callable $operation)
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->retryAttempts) {
            try {
                return $operation();
            } catch (ObsException $e) {
                $lastException = $e;
                $attempts++;

                // Don't retry on authentication or configuration errors
                if (in_array($e->getExceptionCode(), ['AccessDenied', 'InvalidAccessKeyId', 'SignatureDoesNotMatch', 'NoSuchBucket'])) {
                    throw $e;
                }

                if ($attempts >= $this->retryAttempts) {
                    break;
                }

                // Exponential backoff
                $delay = $this->retryDelay * pow(2, $attempts - 1);
                usleep($delay * 1000000); // Convert to microseconds
            }
        }

        if ($lastException === null) {
            throw new \RuntimeException('Unexpected error in retry logic');
        }

        throw $lastException;
    }

    /**
     * Log an operation if logging is enabled
     *
     * @param  string  $operation  The operation name
     * @param  string  $path  The file path
     * @param  float  $duration  The operation duration
     * @param  array<string, mixed>  $context  Additional context
     */
    private function logOperation(string $operation, string $path, float $duration, array $context = []): void
    {
        if (! $this->loggingEnabled || ! $this->logOperations) {
            return;
        }

        $logData = array_merge([
            'operation' => $operation,
            'path' => $path,
            'duration' => $duration,
            'bucket' => $this->bucket,
        ], $context);

        if (function_exists('logger')) {
            logger()->info('Huawei OBS operation', $logData);
        }
    }

    /**
     * Log an error if error logging is enabled
     *
     * @param  string  $operation  The operation name
     * @param  string  $path  The file path
     * @param  \Throwable  $exception  The exception
     */
    private function logError(string $operation, string $path, \Throwable $exception): void
    {
        if (! $this->loggingEnabled || ! $this->logErrors) {
            return;
        }

        $logData = [
            'operation' => $operation,
            'path' => $path,
            'bucket' => $this->bucket,
            'error' => $exception->getMessage(),
            'code' => $exception instanceof ObsException ? $exception->getExceptionCode() : null,
        ];

        if (function_exists('logger')) {
            logger()->error('Huawei OBS error', $logData);
        }
    }

    public function __construct(
        string $accessKeyId,
        string $secretAccessKey,
        string $bucket,
        string $endpoint,
        ?string $prefix = null,
        ?Client $httpClient = null,
        ?string $securityToken = null,
        int $retryAttempts = 3,
        int $retryDelay = 1,
        bool $loggingEnabled = false,
        bool $logOperations = false,
        bool $logErrors = true
    ) {
        $this->bucket = $bucket;
        $this->prefix = $prefix;
        $this->retryAttempts = $retryAttempts;
        $this->retryDelay = $retryDelay;
        $this->loggingEnabled = $loggingEnabled;
        $this->logOperations = $logOperations;
        $this->logErrors = $logErrors;

        $config = [
            'key' => $accessKeyId,
            'secret' => $secretAccessKey,
            'endpoint' => $endpoint,
            'ssl_verify' => false,
            'http_client' => $httpClient,
        ];

        if ($securityToken !== null) {
            $config['security_token'] = $securityToken;
        }

        $this->client = new ObsClient($config);
    }

    public function fileExists(string $path): bool
    {
        try {
            $this->checkAuthentication();

            $key = $this->getKey($path);
            $result = $this->client->getObjectMetadata([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            return $result['HttpStatusCode'] === 200;
        } catch (ObsException $e) {
            if ($e->getExceptionCode() === 'NoSuchResource') {
                return false;
            }

            throw UnableToCheckFileExistence::forLocation($path, $e);
        } catch (\RuntimeException $e) {
            // Re-throw authentication errors
            throw $e;
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            $this->checkAuthentication();

            $key = $this->getKey($path);
            $key = rtrim($key, '/').'/';

            $result = $this->client->listObjects([
                'Bucket' => $this->bucket,
                'Prefix' => $key,
                'MaxKeys' => 1,
            ]);

            return ! empty($result['Contents']);
        } catch (ObsException $e) {
            throw UnableToCheckDirectoryExistence::forLocation($path, $e);
        } catch (\RuntimeException $e) {
            // Re-throw authentication errors
            throw $e;
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->checkAuthentication();

            $key = $this->getKey($path);
            $visibility = $config->get('visibility', Visibility::PRIVATE);

            $options = [
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => $contents,
                'ACL' => $this->visibilityToAcl($visibility),
            ];

            $mimetype = $config->get('mimetype');
            if ($mimetype !== null) {
                $options['ContentType'] = $mimetype;
            }

            $this->client->putObject($options);
        } catch (ObsException $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        } catch (\RuntimeException $e) {
            // Re-throw authentication errors
            throw $e;
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        try {
            $this->checkAuthentication();

            $key = $this->getKey($path);
            $visibility = $config->get('visibility', Visibility::PRIVATE);

            $options = [
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => $contents,
                'ACL' => $this->visibilityToAcl($visibility),
            ];

            $mimetype = $config->get('mimetype');
            if ($mimetype !== null) {
                $options['ContentType'] = $mimetype;
            }

            $this->client->putObject($options);
        } catch (ObsException $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        } catch (\RuntimeException $e) {
            // Re-throw authentication errors
            throw $e;
        }
    }

    public function read(string $path): string
    {
        try {
            $this->checkAuthentication();

            $key = $this->getKey($path);
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            return $result['Body'];
        } catch (ObsException $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        } catch (\RuntimeException $e) {
            // Re-throw authentication errors
            throw $e;
        }
    }

    public function readStream(string $path)
    {
        try {
            $this->checkAuthentication();

            $key = $this->getKey($path);
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            $stream = fopen('php://temp', 'r+');
            if ($stream === false) {
                throw new \RuntimeException('Unable to create temporary stream');
            }

            fwrite($stream, (string) $result['Body']);
            rewind($stream);

            return $stream;
        } catch (ObsException $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        } catch (\RuntimeException $e) {
            // Re-throw authentication errors
            throw $e;
        }
    }

    public function delete(string $path): void
    {
        try {
            $key = $this->getKey($path);
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);
        } catch (ObsException $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $key = $this->getKey($path);
            $key = rtrim($key, '/').'/';

            // List all objects with the prefix
            $objects = [];
            $marker = null;

            do {
                $result = $this->client->listObjects([
                    'Bucket' => $this->bucket,
                    'Prefix' => $key,
                    'Marker' => $marker,
                    'MaxKeys' => 1000,
                ]);

                if (! empty($result['Contents'])) {
                    foreach ($result['Contents'] as $object) {
                        $objects[] = ['Key' => $object['Key']];
                    }
                }

                $marker = $result['NextMarker'] ?? null;
            } while ($marker !== null);

            // Delete all objects in the directory
            if (! empty($objects)) {
                $this->client->deleteObjects([
                    'Bucket' => $this->bucket,
                    'Objects' => $objects,
                ]);
            }
        } catch (ObsException $e) {
            throw UnableToDeleteDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $key = $this->getKey($path);
            $key = rtrim($key, '/').'/';

            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => '',
            ]);
        } catch (ObsException $e) {
            throw UnableToCreateDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        try {
            $key = $this->getKey($path);
            $this->client->setObjectAcl([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'ACL' => $this->visibilityToAcl($visibility),
            ]);
        } catch (ObsException $e) {
            throw UnableToSetVisibility::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function visibility(string $path): FileAttributes
    {
        try {
            $key = $this->getKey($path);
            $result = $this->client->getObjectAcl([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            $visibility = $this->aclToVisibility($result['Grants'] ?? []);

            return new FileAttributes($path, null, $visibility);
        } catch (ObsException $e) {
            throw UnableToRetrieveMetadata::visibility($path, $e->getMessage(), $e);
        }
    }

    public function mimeType(string $path): FileAttributes
    {
        try {
            $key = $this->getKey($path);
            $result = $this->client->getObjectMetadata([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            $mimeType = $result['ContentType'] ?? 'application/octet-stream';

            return new FileAttributes($path, null, null, null, $mimeType);
        } catch (ObsException $e) {
            throw UnableToRetrieveMetadata::mimeType($path, $e->getMessage(), $e);
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            $key = $this->getKey($path);
            $result = $this->client->getObjectMetadata([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            $lastModified = strtotime($result['LastModified'] ?? 'now');

            return new FileAttributes($path, null, null, $lastModified);
        } catch (ObsException $e) {
            throw UnableToRetrieveMetadata::lastModified($path, $e->getMessage(), $e);
        }
    }

    public function fileSize(string $path): FileAttributes
    {
        try {
            $key = $this->getKey($path);
            $result = $this->client->getObjectMetadata([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            $fileSize = (int) ($result['ContentLength'] ?? 0);

            return new FileAttributes($path, $fileSize);
        } catch (ObsException $e) {
            throw UnableToRetrieveMetadata::fileSize($path, $e->getMessage(), $e);
        }
    }

    public function listContents(string $path, bool $deep): iterable
    {
        try {
            $this->checkAuthentication();

            $key = $this->getKey($path);
            $key = rtrim($key, '/').'/';

            $marker = null;
            do {
                $options = [
                    'Bucket' => $this->bucket,
                    'Prefix' => $key,
                    'Delimiter' => $deep ? null : '/',
                    'MaxKeys' => 1000,
                ];

                if ($marker !== null) {
                    $options['Marker'] = $marker;
                }

                $result = $this->client->listObjects($options);

                // Handle files
                if (! empty($result['Contents'])) {
                    foreach ($result['Contents'] as $object) {
                        $objectKey = $object['Key'];

                        // Skip the directory marker itself
                        if ($objectKey === $key) {
                            continue;
                        }

                        $relativePath = $this->getRelativePath($objectKey);

                        yield new FileAttributes(
                            $relativePath,
                            (int) $object['Size'],
                            $this->aclToVisibility($object['Grants'] ?? []),
                            (int) strtotime($object['LastModified'])
                        );
                    }
                }

                // Handle directories (CommonPrefixes)
                if (! empty($result['CommonPrefixes'])) {
                    foreach ($result['CommonPrefixes'] as $prefix) {
                        $prefixKey = $prefix['Prefix'];
                        $relativePath = $this->getRelativePath(rtrim($prefixKey, '/'));

                        yield new DirectoryAttributes($relativePath);
                    }
                }

                $marker = $result['NextMarker'] ?? null;
            } while ($marker !== null);
        } catch (ObsException $e) {
            throw new \RuntimeException('Unable to list contents: '.$e->getMessage(), 0, $e);
        } catch (\RuntimeException $e) {
            // Re-throw authentication errors
            throw $e;
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $sourceKey = $this->getKey($source);
            $destinationKey = $this->getKey($destination);

            // Copy the object
            $this->client->copyObject([
                'Bucket' => $this->bucket,
                'Key' => $destinationKey,
                'CopySource' => $this->bucket.'/'.$sourceKey,
            ]);

            // Delete the original
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $sourceKey,
            ]);
        } catch (ObsException $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $sourceKey = $this->getKey($source);
            $destinationKey = $this->getKey($destination);

            $this->client->copyObject([
                'Bucket' => $this->bucket,
                'Key' => $destinationKey,
                'CopySource' => $this->bucket.'/'.$sourceKey,
            ]);
        } catch (ObsException $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

    /**
     * Refresh credentials (useful for temporary credentials)
     *
     * @param  string  $accessKeyId  New access key ID
     * @param  string  $secretAccessKey  New secret access key
     * @param  string|null  $securityToken  New security token (optional)
     */
    public function refreshCredentials(string $accessKeyId, string $secretAccessKey, ?string $securityToken = null): void
    {
        $this->client->refresh($accessKeyId, $secretAccessKey, $securityToken);
        $this->refreshAuthentication();
    }

    /**
     * Refresh authentication cache
     */
    public function refreshAuthentication(): void
    {
        $this->authenticated = null;
        $this->authCacheExpiry = null;
        $this->checkAuthentication();
    }

    /**
     * Create a signed URL for temporary access to an object
     *
     * @param  string  $path  The object path
     * @param  string  $method  HTTP method (GET, PUT, DELETE, etc.)
     * @param  int  $expires  Expiration time in seconds (default: 3600)
     * @param  array<string, string>  $headers  Additional headers to sign
     * @return string The signed URL
     *
     * @throws UnableToCreateSignedUrl When unable to create signed URL
     */
    public function createSignedUrl(string $path, string $method = 'GET', int $expires = 3600, array $headers = []): string
    {
        $startTime = microtime(true);

        try {
            $this->checkAuthentication();

            $key = $this->getKey($path);

            $result = $this->withRetry(function () use ($method, $key, $expires, $headers) {
                return $this->client->createSignedUrl([
                    'Method' => $method,
                    'Bucket' => $this->bucket,
                    'Key' => $key,
                    'Expires' => $expires,
                    'Headers' => $headers,
                ]);
            });

            $duration = microtime(true) - $startTime;
            $this->logOperation('createSignedUrl', $path, $duration, ['method' => $method, 'expires' => $expires]);

            return $result['SignedUrl'];
        } catch (ObsException $e) {
            $this->logError('createSignedUrl', $path, $e);

            throw UnableToCreateSignedUrl::forLocation($path, $e);
        } catch (\RuntimeException $e) {
            // Re-throw authentication errors
            throw $e;
        }
    }

    /**
     * Create a post signature for direct browser uploads
     *
     * @param  string  $path  The object path
     * @param  array<int, array<string, mixed>>  $conditions  Additional conditions for the post policy
     * @param  int  $expires  Expiration time in seconds (default: 3600)
     * @return array<string, mixed> The post signature data
     *
     * @throws UnableToCreatePostSignature When unable to create post signature
     */
    public function createPostSignature(string $path, array $conditions = [], int $expires = 3600): array
    {
        $startTime = microtime(true);

        try {
            $this->checkAuthentication();

            $key = $this->getKey($path);

            $result = $this->withRetry(function () use ($key, $expires, $conditions) {
                return $this->client->createPostSignature([
                    'Bucket' => $this->bucket,
                    'Key' => $key,
                    'Expires' => $expires,
                    'Conditions' => $conditions,
                ]);
            });

            $duration = microtime(true) - $startTime;
            $this->logOperation('createPostSignature', $path, $duration, ['expires' => $expires]);

            return $result;
        } catch (ObsException $e) {
            $this->logError('createPostSignature', $path, $e);

            throw UnableToCreatePostSignature::forLocation($path, $e);
        } catch (\RuntimeException $e) {
            // Re-throw authentication errors
            throw $e;
        }
    }

    /**
     * Set object tags (metadata)
     *
     * @param  string  $path  The object path
     * @param  array<string, string>  $tags  The tags to set
     *
     * @throws UnableToSetObjectTags When unable to set object tags
     */
    public function setObjectTags(string $path, array $tags): void
    {
        $startTime = microtime(true);

        try {
            $this->checkAuthentication();

            $key = $this->getKey($path);

            $this->withRetry(function () use ($key, $tags) {
                return $this->client->setObjectTagging([
                    'Bucket' => $this->bucket,
                    'Key' => $key,
                    'TagSet' => $tags,
                ]);
            });

            $duration = microtime(true) - $startTime;
            $this->logOperation('setObjectTags', $path, $duration, ['tags_count' => count($tags)]);
        } catch (ObsException $e) {
            $this->logError('setObjectTags', $path, $e);

            throw UnableToSetObjectTags::forLocation($path, $e);
        } catch (\RuntimeException $e) {
            // Re-throw authentication errors
            throw $e;
        }
    }

    /**
     * Get object tags (metadata)
     *
     * @param  string  $path  The object path
     * @return array<string, string> The object tags
     *
     * @throws UnableToGetObjectTags When unable to get object tags
     */
    public function getObjectTags(string $path): array
    {
        $startTime = microtime(true);

        try {
            $this->checkAuthentication();

            $key = $this->getKey($path);

            $result = $this->withRetry(function () use ($key) {
                return $this->client->getObjectTagging([
                    'Bucket' => $this->bucket,
                    'Key' => $key,
                ]);
            });

            $duration = microtime(true) - $startTime;
            $this->logOperation('getObjectTags', $path, $duration);

            return $result['TagSet'] ?? [];
        } catch (ObsException $e) {
            $this->logError('getObjectTags', $path, $e);

            throw UnableToGetObjectTags::forLocation($path, $e);
        } catch (\RuntimeException $e) {
            // Re-throw authentication errors
            throw $e;
        }
    }

    /**
     * Delete object tags (metadata)
     *
     * @param  string  $path  The object path
     *
     * @throws UnableToDeleteObjectTags When unable to delete object tags
     */
    public function deleteObjectTags(string $path): void
    {
        $startTime = microtime(true);

        try {
            $this->checkAuthentication();

            $key = $this->getKey($path);

            $this->withRetry(function () use ($key) {
                return $this->client->deleteObjectTagging([
                    'Bucket' => $this->bucket,
                    'Key' => $key,
                ]);
            });

            $duration = microtime(true) - $startTime;
            $this->logOperation('deleteObjectTags', $path, $duration);
        } catch (ObsException $e) {
            $this->logError('deleteObjectTags', $path, $e);

            throw UnableToDeleteObjectTags::forLocation($path, $e);
        } catch (\RuntimeException $e) {
            // Re-throw authentication errors
            throw $e;
        }
    }

    /**
     * Restore an archived object
     *
     * @param  string  $path  The object path
     * @param  int  $days  Number of days to restore for (default: 1)
     *
     * @throws UnableToRestoreObject When unable to restore object
     */
    public function restoreObject(string $path, int $days = 1): void
    {
        $startTime = microtime(true);

        try {
            $this->checkAuthentication();

            $key = $this->getKey($path);

            $this->withRetry(function () use ($key, $days) {
                return $this->client->restoreObject([
                    'Bucket' => $this->bucket,
                    'Key' => $key,
                    'Days' => $days,
                ]);
            });

            $duration = microtime(true) - $startTime;
            $this->logOperation('restoreObject', $path, $duration, ['days' => $days]);
        } catch (ObsException $e) {
            $this->logError('restoreObject', $path, $e);

            throw UnableToRestoreObject::forLocation($path, $e);
        } catch (\RuntimeException $e) {
            // Re-throw authentication errors
            throw $e;
        }
    }

    private function getKey(string $path): string
    {
        $key = ltrim($path, '/');

        if ($this->prefix !== null) {
            $key = ltrim($this->prefix.'/'.$key, '/');
        }

        return $key;
    }

    private function getRelativePath(string $key): string
    {
        if ($this->prefix !== null) {
            $key = preg_replace('/^'.preg_quote($this->prefix.'/', '/').'/', '', $key);
            if ($key === null) {
                $key = '';
            }
        }

        return '/'.ltrim($key, '/');
    }

    private function visibilityToAcl(string $visibility): string
    {
        return match ($visibility) {
            Visibility::PUBLIC => 'public-read',
            Visibility::PRIVATE => 'private',
            default => 'private',
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $grants
     */
    private function aclToVisibility(array $grants): string
    {
        foreach ($grants as $grant) {
            if (isset($grant['Grantee']['URI']) && $grant['Grantee']['URI'] === 'http://acs.amazonaws.com/groups/global/AllUsers') {
                if (isset($grant['Permission']) && in_array($grant['Permission'], ['READ', 'READ_ACP'])) {
                    return Visibility::PUBLIC;
                }
            }
        }

        return Visibility::PRIVATE;
    }
}
