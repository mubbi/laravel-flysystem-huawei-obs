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
use Obs\ObsException;

class HuaweiObsAdapter extends AbstractHuaweiObsAdapter implements FilesystemAdapter
{
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
            // Check for file not found errors
            if ($this->isNotFoundError($e)) {
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
            // Check for directory not found errors
            if ($this->isNotFoundError($e)) {
                return false;
            }

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

            // Convert the CheckoutStream to string
            return (string) $result['Body'];
        } catch (ObsException $e) {
            // Check for file not found errors
            if ($this->isNotFoundError($e)) {
                throw UnableToReadFile::fromLocation($path, 'File not found', $e);
            }

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
            // Check for file not found errors
            if ($this->isNotFoundError($e)) {
                throw UnableToReadFile::fromLocation($path, 'File not found', $e);
            }

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
            $lastMarker = null; // Track the last marker to detect stuck pagination
            $maxIterations = 100; // Safety limit to prevent infinite loops
            $iterationCount = 0;

            do {
                $iterationCount++;

                // Safety check to prevent infinite loops
                if ($iterationCount > $maxIterations) {
                    throw new \RuntimeException('Maximum iterations reached. Possible infinite loop detected.');
                }

                // Additional safety check: if we've seen this marker before, break
                if ($marker !== null && $marker === $lastMarker) {
                    break;
                }

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

                // Get the next marker
                $nextMarker = $result['NextMarker'] ?? null;

                // Safety checks to prevent infinite loop
                if ($nextMarker === null) {
                    // No more pages
                    break;
                }

                if ($nextMarker === $marker) {
                    // Same marker returned - potential infinite loop
                    break;
                }

                // Update markers for next iteration
                $lastMarker = $marker;
                $marker = $nextMarker;

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

            return new FileAttributes($path, 0, $visibility);
        } catch (ObsException $e) {
            // Check for file not found errors
            if ($this->isNotFoundError($e)) {
                throw UnableToRetrieveMetadata::visibility($path, 'File not found', $e);
            }

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

            return new FileAttributes($path, 0, null, time(), $mimeType);
        } catch (ObsException $e) {
            // Check for file not found errors
            if ($this->isNotFoundError($e)) {
                throw UnableToRetrieveMetadata::mimeType($path, 'File not found', $e);
            }

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

            return new FileAttributes($path, 0, null, $lastModified);
        } catch (ObsException $e) {
            // Check for file not found errors
            if ($this->isNotFoundError($e)) {
                throw UnableToRetrieveMetadata::lastModified($path, 'File not found', $e);
            }

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
            // Check for file not found errors
            if ($this->isNotFoundError($e)) {
                throw UnableToRetrieveMetadata::fileSize($path, 'File not found', $e);
            }

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
            $processedKeys = [];
            $maxIterations = 100; // Safety limit to prevent infinite loops
            $iterationCount = 0;
            $lastMarker = null; // Track the last marker to detect stuck pagination

            do {
                $iterationCount++;

                // Safety check to prevent infinite loops
                if ($iterationCount > $maxIterations) {
                    throw new \RuntimeException('Maximum iterations reached. Possible infinite loop detected.');
                }

                // Additional safety check: if we've seen this marker before, break
                if ($marker !== null && $marker === $lastMarker) {
                    break;
                }

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

                        // Check if we've already processed this key to prevent duplicates
                        if (in_array($objectKey, $processedKeys)) {
                            continue;
                        }

                        $processedKeys[] = $objectKey;
                        $relativePath = $this->getRelativePath($objectKey);

                        yield new FileAttributes(
                            $relativePath,
                            (int) ($object['Size'] ?? 0),
                            $this->aclToVisibility($object['Grants'] ?? []),
                            isset($object['LastModified']) && $object['LastModified'] ? (int) strtotime($object['LastModified']) : time()
                        );
                    }
                }

                // Handle directories (CommonPrefixes)
                if (! empty($result['CommonPrefixes'])) {
                    foreach ($result['CommonPrefixes'] as $prefix) {
                        $prefixKey = $prefix['Prefix'];

                        // Check if we've already processed this prefix to prevent duplicates
                        if (in_array($prefixKey, $processedKeys)) {
                            continue;
                        }

                        $processedKeys[] = $prefixKey;
                        $relativePath = $this->getRelativePath(rtrim($prefixKey, '/'));

                        yield new DirectoryAttributes($relativePath);
                    }
                }

                // Get the next marker
                $nextMarker = $result['NextMarker'] ?? null;

                // Safety checks to prevent infinite loop
                if ($nextMarker === null) {
                    // No more pages
                    break;
                }

                if ($nextMarker === $marker) {
                    // Same marker returned - potential infinite loop
                    break;
                }

                // Update markers for next iteration
                $lastMarker = $marker;
                $marker = $nextMarker;

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
     * Get the URL for the file at the given path.
     *
     * For public objects, returns a direct URL.
     * For private objects, returns a signed URL with 1-hour expiration.
     *
     * @throws \RuntimeException
     */
    public function url(string $path): string
    {
        try {
            $this->checkAuthentication();

            $key = $this->getKey($path);

            // Check if the object is public
            $result = $this->client->getObjectAcl([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            $isPublic = false;
            if (isset($result['Grants'])) {
                foreach ($result['Grants'] as $grant) {
                    if (isset($grant['Grantee']['URI']) &&
                        $grant['Grantee']['URI'] === 'http://acs.amazonaws.com/groups/global/AllUsers') {
                        if (isset($grant['Permission']) && in_array($grant['Permission'], ['READ', 'READ_ACP'])) {
                            $isPublic = true;

                            break;
                        }
                    }
                }
            }

            if ($isPublic) {
                // Construct the public URL
                $endpoint = rtrim($this->client->getConfig()['endpoint'], '/');

                return $endpoint.'/'.$this->bucket.'/'.$key;
            } else {
                // For private objects, return a signed URL
                return $this->createSignedUrl($path, 'GET', 3600);
            }
        } catch (ObsException $e) {
            if ($this->isNotFoundError($e)) {
                throw new \RuntimeException('File not found: '.$path);
            }

            throw new \RuntimeException('Unable to retrieve URL: '.$e->getMessage());
        } catch (\RuntimeException $e) {
            // Re-throw authentication errors
            throw $e;
        }
    }

    /**
     * Get a temporary URL for the file at the given path.
     *
     * This method is used by Laravel's FilesystemAdapter::temporaryUrl() method.
     *
     * @param  string  $path  The file path
     * @param  \DateTimeInterface  $expiration  The expiration time
     * @param  array{method?: string, headers?: array<string, string>}  $options  Additional options
     * @return string The temporary URL
     *
     * @throws \RuntimeException
     */
    public function getTemporaryUrl(string $path, \DateTimeInterface $expiration, array $options = []): string
    {
        try {
            $this->checkAuthentication();

            $key = $this->getKey($path);
            $method = $options['method'] ?? 'GET';
            $headers = $options['headers'] ?? [];

            // Calculate expiration time in seconds from now
            $expiresIn = $expiration->getTimestamp() - time();

            // Ensure minimum and maximum expiration times
            $expiresIn = max(1, min($expiresIn, 604800)); // Between 1 second and 7 days

            return $this->createSignedUrl($path, $method, $expiresIn, $headers);

        } catch (ObsException $e) {
            if ($this->isNotFoundError($e)) {
                throw new \RuntimeException('File not found: '.$path);
            }

            throw new \RuntimeException('Unable to create temporary URL: '.$e->getMessage());
        } catch (\RuntimeException $e) {
            // Re-throw authentication errors
            throw $e;
        }
    }

    /**
     * Get the URL for the file at the given path.
     *
     * This method is used by Laravel's FilesystemAdapter::url() method.
     * It's an alias for the url() method for compatibility.
     *
     * @param  string  $path  The file path
     * @return string The URL
     *
     * @throws \RuntimeException
     */
    public function getUrl(string $path): string
    {
        return $this->url($path);
    }

    /**
     * Get a temporary upload URL for the file at the given path.
     *
     * This method is used by Laravel's FilesystemAdapter for direct uploads.
     *
     * @param  string  $path  The file path
     * @param  \DateTimeInterface  $expiration  The expiration time
     * @param  array{method?: string, headers?: array<string, string>}  $options  Additional options
     * @return string The temporary upload URL
     *
     * @throws \RuntimeException
     */
    public function temporaryUploadUrl(string $path, \DateTimeInterface $expiration, array $options = []): string
    {
        // For upload URLs, we typically want PUT method
        $options['method'] = $options['method'] ?? 'PUT';

        return $this->getTemporaryUrl($path, $expiration, $options);
    }

    /**
     * Get all files in the bucket (recursive)
     *
     * @return array<string>
     */
    public function allFiles(): array
    {
        $files = [];
        foreach ($this->listContents('', true) as $item) {
            if ($item instanceof \League\Flysystem\FileAttributes) {
                $files[] = $item->path();
            }
        }

        return $files;
    }

    /**
     * Get all directories in the bucket (recursive)
     *
     * @return array<string>
     */
    public function allDirectories(): array
    {
        $directories = [];
        foreach ($this->listContents('', true) as $item) {
            if ($item instanceof \League\Flysystem\DirectoryAttributes) {
                $directories[] = $item->path();
            }
        }

        return $directories;
    }

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
            if ($item instanceof \League\Flysystem\FileAttributes) {
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
            if ($item instanceof \League\Flysystem\DirectoryAttributes) {
                $directories[] = $item->path();
            }
        }

        return $directories;
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
        $files = [];
        $processedKeys = 0;
        $startTime = time();

        foreach ($this->listContentsOptimized('', true, $maxKeys, $timeout) as $item) {
            if ($item instanceof \League\Flysystem\FileAttributes) {
                $files[] = $item->path();
                $processedKeys++;

                // Check timeout
                if ($timeout > 0 && (time() - $startTime) >= $timeout) {
                    break;
                }
            }
        }

        return $files;
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
        $directories = [];
        $processedKeys = 0;
        $startTime = time();

        foreach ($this->listContentsOptimized('', true, $maxKeys, $timeout) as $item) {
            if ($item instanceof \League\Flysystem\DirectoryAttributes) {
                $directories[] = $item->path();
                $processedKeys++;

                // Check timeout
                if ($timeout > 0 && (time() - $startTime) >= $timeout) {
                    break;
                }
            }
        }

        return $directories;
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
        try {
            $this->checkAuthentication();

            $key = $this->getKey($path);
            $key = rtrim($key, '/').'/';

            $marker = null;
            $processedKeys = [];
            $processedCount = 0;
            $startTime = time();
            $maxIterations = 100; // Safety limit to prevent infinite loops
            $iterationCount = 0;
            $lastMarker = null; // Track the last marker to detect stuck pagination

            do {
                $iterationCount++;

                // Check timeout before making API call
                if ($timeout > 0 && (time() - $startTime) >= $timeout) {
                    break;
                }

                // Safety check to prevent infinite loops
                if ($iterationCount > $maxIterations) {
                    throw new \RuntimeException('Maximum iterations reached. Possible infinite loop detected.');
                }

                // Additional safety check: if we've seen this marker before, break
                if ($marker !== null && $marker === $lastMarker) {
                    break;
                }

                $options = [
                    'Bucket' => $this->bucket,
                    'Prefix' => $key,
                    'Delimiter' => $deep ? null : '/',
                    'MaxKeys' => min(1000, $maxKeys > 0 ? $maxKeys - $processedCount : 1000),
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

                        // Check if we've already processed this key to prevent duplicates
                        if (in_array($objectKey, $processedKeys)) {
                            continue;
                        }

                        $processedKeys[] = $objectKey;
                        $relativePath = $this->getRelativePath($objectKey);

                        yield new FileAttributes(
                            $relativePath,
                            $object['Size'] ?? 0,
                            $this->aclToVisibility($object['Grants'] ?? []),
                            $object['LastModified'] ? (int) strtotime($object['LastModified']) : time()
                        );

                        $processedCount++;

                        // Check if we've reached the maximum keys limit
                        if ($maxKeys > 0 && $processedCount >= $maxKeys) {
                            return;
                        }
                    }
                }

                // Handle directories (CommonPrefixes)
                if (! empty($result['CommonPrefixes'])) {
                    foreach ($result['CommonPrefixes'] as $prefix) {
                        $prefixKey = $prefix['Prefix'];

                        // Check if we've already processed this prefix to prevent duplicates
                        if (in_array($prefixKey, $processedKeys)) {
                            continue;
                        }

                        $processedKeys[] = $prefixKey;
                        $relativePath = $this->getRelativePath(rtrim($prefixKey, '/'));

                        yield new DirectoryAttributes($relativePath);

                        $processedCount++;

                        // Check if we've reached the maximum keys limit
                        if ($maxKeys > 0 && $processedCount >= $maxKeys) {
                            return;
                        }
                    }
                }

                // Get the next marker
                $nextMarker = $result['NextMarker'] ?? null;

                // Safety checks to prevent infinite loop
                if ($nextMarker === null) {
                    // No more pages
                    break;
                }

                if ($nextMarker === $marker) {
                    // Same marker returned - potential infinite loop
                    break;
                }

                // Update markers for next iteration
                $lastMarker = $marker;
                $marker = $nextMarker;

            } while ($marker !== null);
        } catch (ObsException $e) {
            throw new \RuntimeException('Unable to list contents: '.$e->getMessage(), 0, $e);
        } catch (\RuntimeException $e) {
            // Re-throw authentication errors
            throw $e;
        }
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
        $totalSize = 0;
        $fileTypes = [];
        $fileCount = 0;
        $directoryCount = 0;
        $processedCount = 0;
        $startTime = time();

        foreach ($this->listContentsOptimized('', true, $maxFiles, $timeout) as $item) {
            if ($item instanceof \League\Flysystem\FileAttributes) {
                $fileCount++;
                $size = $item->fileSize() ?? 0;
                $totalSize += $size;

                $extension = pathinfo($item->path(), PATHINFO_EXTENSION);
                $fileTypes[$extension] = ($fileTypes[$extension] ?? 0) + 1;
            } elseif ($item instanceof \League\Flysystem\DirectoryAttributes) {
                $directoryCount++;
            }

            $processedCount++;

            // Check timeout
            if ($timeout > 0 && (time() - $startTime) >= $timeout) {
                break;
            }
        }

        return [
            'total_files' => $fileCount,
            'total_directories' => $directoryCount,
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'file_types' => $fileTypes,
            'processed_count' => $processedCount,
            'processing_time_seconds' => time() - $startTime,
            'has_more_files' => $maxFiles > 0 && $processedCount >= $maxFiles,
        ];
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
        try {
            $key = $this->getKey($path);
            $result = $this->client->getObjectMetadata([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            return (int) ($result['ContentLength'] ?? 0);
        } catch (ObsException $e) {
            throw UnableToRetrieveMetadata::fileSize($path, $e->getMessage(), $e);
        }
    }

    /**
     * Get the visibility of a file (Laravel Storage facade compatibility)
     */
    public function getVisibility(string $path): string
    {
        $attributes = $this->visibility($path);
        $visibility = $attributes->visibility();

        if ($visibility === null) {
            return 'private';
        }

        return $visibility;
    }

    /**
     * Get the mime type of a file (Laravel Storage facade compatibility)
     */
    public function getMimeType(string $path): string
    {
        $attributes = $this->mimeType($path);

        return $attributes->mimeType() ?? 'application/octet-stream';
    }

    /**
     * Get the last modified timestamp of a file (Laravel Storage facade compatibility)
     */
    public function getLastModified(string $path): int
    {
        $attributes = $this->lastModified($path);

        return $attributes->lastModified() ?? time();
    }
}
