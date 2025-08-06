<?php

declare(strict_types=1);

/**
 * Abstract Huawei OBS Adapter
 *
 * Base class containing common functionality for both Flysystem v2 and v3 adapters.
 *
 * @author  Mubbasher Ahmed <hello@mubbi.me>
 *
 * @link    https://mubbi.me
 *
 * @license MIT
 */

namespace LaravelFlysystemHuaweiObs;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToCreatePostSignature;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToCreateSignedUrl;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToDeleteObjectTags;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToGetObjectTags;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToRestoreObject;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToSetObjectTags;
use Obs\ObsClient;
use Obs\ObsException;

abstract class AbstractHuaweiObsAdapter
{
    protected ObsClient $client;

    protected string $bucket;

    protected ?string $prefix;

    protected ?bool $authenticated = null;

    protected ?float $authCacheExpiry = null;

    protected int $retryAttempts;

    protected int $retryDelay;

    protected bool $loggingEnabled;

    protected bool $logOperations;

    protected bool $logErrors;

    public function __construct(
        string $accessKeyId,
        string $secretAccessKey,
        string $bucket,
        string $endpoint,
        ?string $prefix = null,
        ?ClientInterface $httpClient = null,
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

    /**
     * Check if the client is properly authenticated
     *
     * @throws \RuntimeException If authentication fails
     */
    protected function checkAuthentication(): void
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

                if ($this->isAuthenticationError($e)) {
                    throw new \RuntimeException(
                        'Authentication failed. Please check your Huawei OBS credentials (Access Key ID, Secret Access Key, and Security Token if using temporary credentials). Error: '.$e->getMessage(),
                        0,
                        $e
                    );
                }

                if ($this->isBucketError($e)) {
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
    protected function withRetry(callable $operation)
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
                if ($this->isAuthenticationError($e) || $this->isBucketError($e)) {
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
    protected function logOperation(string $operation, string $path, float $duration, array $context = []): void
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
    protected function logError(string $operation, string $path, \Throwable $exception): void
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

    protected function getKey(string $path): string
    {
        $key = ltrim($path, '/');

        if ($this->prefix !== null) {
            $key = ltrim($this->prefix.'/'.$key, '/');
        }

        return $key;
    }

    protected function getRelativePath(string $key): string
    {
        if ($this->prefix !== null) {
            $key = preg_replace('/^'.preg_quote($this->prefix.'/', '/').'/', '', $key);
            if ($key === null) {
                $key = '';
            }
        }

        return '/'.ltrim($key, '/');
    }

    protected function visibilityToAcl(string $visibility): string
    {
        return match ($visibility) {
            'public', 'public-read' => 'public-read',
            'private' => 'private',
            default => 'private',
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $grants
     */
    protected function aclToVisibility(array $grants): string
    {
        foreach ($grants as $grant) {
            if (isset($grant['Grantee']['URI']) && $grant['Grantee']['URI'] === 'http://acs.amazonaws.com/groups/global/AllUsers') {
                if (isset($grant['Permission']) && in_array($grant['Permission'], ['READ', 'READ_ACP'])) {
                    return 'public';
                }
            }
        }

        return 'private';
    }

    /**
     * Extract error code from ObsException with fallback to response headers
     *
     * @param ObsException $exception
     * @return string|null
     */
    protected function extractErrorCode(ObsException $exception): ?string
    {
        $errorCode = $exception->getExceptionCode();
        
        // If getExceptionCode() returns null, try to extract from response headers
        if ($errorCode === null) {
            try {
                $reflection = new \ReflectionClass($exception);
                $responseProperty = $reflection->getProperty('response');
                $responseProperty->setAccessible(true);
                $response = $responseProperty->getValue($exception);
                
                if ($response !== null && method_exists($response, 'getHeader')) {
                    $errorCodeHeader = $response->getHeader('x-obs-error-code');
                    if (!empty($errorCodeHeader)) {
                        $errorCode = $errorCodeHeader[0];
                    }
                }
            } catch (\ReflectionException $reflectionException) {
                // If reflection fails, return null
            }
        }
        
        return $errorCode;
    }

    /**
     * Check if an ObsException represents a "not found" error
     *
     * @param ObsException $exception
     * @return bool
     */
    protected function isNotFoundError(ObsException $exception): bool
    {
        $errorCode = $this->extractErrorCode($exception);
        $errorMessage = $exception->getMessage();
        
        return $errorCode === 'NoSuchKey' || 
               $errorCode === 'NoSuchResource' || 
               str_contains($errorMessage, 'NoSuchKey') || 
               str_contains($errorMessage, 'NoSuchResource');
    }

    /**
     * Check if an ObsException represents an authentication error
     *
     * @param ObsException $exception
     * @return bool
     */
    protected function isAuthenticationError(ObsException $exception): bool
    {
        $errorCode = $this->extractErrorCode($exception);
        
        return in_array($errorCode, ['AccessDenied', 'InvalidAccessKeyId', 'SignatureDoesNotMatch']);
    }

    /**
     * Check if an ObsException represents a bucket error
     *
     * @param ObsException $exception
     * @return bool
     */
    protected function isBucketError(ObsException $exception): bool
    {
        $errorCode = $this->extractErrorCode($exception);
        
        return $errorCode === 'NoSuchBucket';
    }


}
