<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests;

use GuzzleHttp\Client;
use LaravelFlysystemHuaweiObs\HuaweiObsAdapter;
use LaravelFlysystemHuaweiObs\LaravelHuaweiObsAdapter;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use Mockery;
use Obs\ObsClient;
use Obs\ObsException;
use PHPUnit\Framework\TestCase;

class LaravelHuaweiObsAdapterTest extends TestCase
{
    private string $accessKeyId = 'test-key';

    private string $secretAccessKey = 'test-secret';

    private string $bucket = 'test-bucket';

    private string $endpoint = 'https://obs.test.com';

    /** @var \Mockery\MockInterface&\Obs\ObsClient */
    private $mockClient;

    private LaravelHuaweiObsAdapter $adapter;

    private HuaweiObsAdapter $baseAdapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = Mockery::mock(ObsClient::class);

        // Mock authentication check
        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andReturn(['HttpStatusCode' => 200])
            ->byDefault();

        $this->adapter = new LaravelHuaweiObsAdapter(
            $this->accessKeyId,
            $this->secretAccessKey,
            $this->bucket,
            $this->endpoint,
            null,
            null,
            null,
            3, // retryAttempts
            1, // retryDelay
            false, // loggingEnabled
            false, // logOperations
            true   // logErrors
        );

        // Get the base adapter for testing
        $reflection = new \ReflectionClass($this->adapter);
        $adapterProperty = $reflection->getProperty('adapter');
        $adapterProperty->setAccessible(true);
        $this->baseAdapter = $adapterProperty->getValue($this->adapter);

        // Replace the client with our mock
        $baseReflection = new \ReflectionClass($this->baseAdapter);
        $clientProperty = $baseReflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->baseAdapter, $this->mockClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // Test Laravel Storage facade compatibility methods

    public function test_files_method_returns_array_of_file_paths(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => 'uploads/',
                'Delimiter' => '/',
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'uploads/file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                    ],
                    [
                        'Key' => 'uploads/file2.txt',
                        'Size' => 200,
                        'LastModified' => '2023-01-02T00:00:00Z',
                    ],
                ],
            ]);

        $files = $this->adapter->files('uploads');

        $this->assertIsArray($files);
        $this->assertCount(2, $files);
        $this->assertContains('uploads/file1.txt', $files);
        $this->assertContains('uploads/file2.txt', $files);
    }

    public function test_files_method_returns_empty_array_when_no_files(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => 'uploads/',
                'Delimiter' => '/',
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([]);

        $files = $this->adapter->files('uploads');

        $this->assertIsArray($files);
        $this->assertEmpty($files);
    }

    public function test_directories_method_returns_array_of_directory_paths(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => 'uploads/',
                'Delimiter' => '/',
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'CommonPrefixes' => [
                    ['Prefix' => 'uploads/subdir1/'],
                    ['Prefix' => 'uploads/subdir2/'],
                ],
            ]);

        $directories = $this->adapter->directories('uploads');

        $this->assertIsArray($directories);
        $this->assertCount(2, $directories);
        $this->assertContains('uploads/subdir1', $directories);
        $this->assertContains('uploads/subdir2', $directories);
    }

    public function test_directories_method_returns_empty_array_when_no_directories(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => 'uploads/',
                'Delimiter' => '/',
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([]);

        $directories = $this->adapter->directories('uploads');

        $this->assertIsArray($directories);
        $this->assertEmpty($directories);
    }

    public function test_exists_method_returns_true_when_file_exists(): void
    {
        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $exists = $this->adapter->exists('test-file.txt');

        $this->assertTrue($exists);
    }

    public function test_exists_method_returns_false_when_file_does_not_exist(): void
    {
        $exception = new ObsException('NoSuchResource');
        $exception->setExceptionCode('NoSuchResource');

        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'nonexistent-file.txt',
            ])
            ->once()
            ->andThrow($exception);

        $exists = $this->adapter->exists('nonexistent-file.txt');

        $this->assertFalse($exists);
    }

    public function test_size_method_returns_file_size(): void
    {
        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([
                'ContentLength' => 1024,
            ]);

        $size = $this->adapter->size('test-file.txt');

        $this->assertIsInt($size);
        $this->assertEquals(1024, $size);
    }

    public function test_size_method_returns_zero_when_size_not_available(): void
    {
        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([]);

        $size = $this->adapter->size('test-file.txt');

        $this->assertIsInt($size);
        $this->assertEquals(0, $size);
    }

    public function test_get_last_modified_method_returns_timestamp(): void
    {
        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([
                'LastModified' => '2023-01-01T12:00:00Z',
            ]);

        $lastModified = $this->adapter->getLastModified('test-file.txt');

        $this->assertIsInt($lastModified);
        $this->assertGreaterThan(0, $lastModified);
        $this->assertEquals(strtotime('2023-01-01T12:00:00Z'), $lastModified);
    }

    public function test_get_last_modified_method_returns_current_time_when_date_not_available(): void
    {
        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([]);

        $lastModified = $this->adapter->getLastModified('test-file.txt');

        $this->assertIsInt($lastModified);
        $this->assertGreaterThan(0, $lastModified);
        $this->assertGreaterThanOrEqual(time() - 1, $lastModified);
    }

    public function test_get_mime_type_method_returns_mime_type(): void
    {
        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([
                'ContentType' => 'text/plain',
            ]);

        $mimeType = $this->adapter->getMimeType('test-file.txt');

        $this->assertIsString($mimeType);
        $this->assertEquals('text/plain', $mimeType);
    }

    public function test_get_mime_type_method_returns_default_when_mime_type_not_available(): void
    {
        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([]);

        $mimeType = $this->adapter->getMimeType('test-file.txt');

        $this->assertIsString($mimeType);
        $this->assertEquals('application/octet-stream', $mimeType);
    }

    public function test_get_visibility_method_returns_public(): void
    {
        $this->mockClient->shouldReceive('getObjectAcl')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([
                'Grants' => [
                    [
                        'Grantee' => [
                            'URI' => 'http://acs.amazonaws.com/groups/global/AllUsers',
                        ],
                        'Permission' => 'READ',
                    ],
                ],
            ]);

        $visibility = $this->adapter->getVisibility('test-file.txt');

        $this->assertIsString($visibility);
        $this->assertEquals('public', $visibility);
    }

    public function test_get_visibility_method_returns_private(): void
    {
        $this->mockClient->shouldReceive('getObjectAcl')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([
                'Grants' => [
                    [
                        'Grantee' => [
                            'ID' => 'test-user',
                        ],
                        'Permission' => 'READ',
                    ],
                ],
            ]);

        $visibility = $this->adapter->getVisibility('test-file.txt');

        $this->assertIsString($visibility);
        $this->assertEquals('private', $visibility);
    }

    public function test_get_visibility_method_returns_private_when_no_grants(): void
    {
        $this->mockClient->shouldReceive('getObjectAcl')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([]);

        $visibility = $this->adapter->getVisibility('test-file.txt');

        $this->assertIsString($visibility);
        $this->assertEquals('private', $visibility);
    }

    // Test delegation to base adapter methods

    public function test_file_exists_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $result = $this->adapter->fileExists('test-file.txt');

        $this->assertTrue($result);
    }

    public function test_directory_exists_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => 'test-dir/',
                'MaxKeys' => 1,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    ['Key' => 'test-dir/file.txt'],
                ],
            ]);

        $result = $this->adapter->directoryExists('test-dir');

        $this->assertTrue($result);
    }

    public function test_write_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('putObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
                'Body' => 'test content',
                'ACL' => 'private',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $config = new Config;
        $this->adapter->write('test-file.txt', 'test content', $config);

        // If we get here without exception, the test passes
        $this->assertTrue(true);
    }

    public function test_read_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('getObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([
                'Body' => 'test content',
            ]);

        $result = $this->adapter->read('test-file.txt');

        $this->assertEquals('test content', $result);
    }

    public function test_delete_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('deleteObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 204]);

        $this->adapter->delete('test-file.txt');

        // If we get here without exception, the test passes
        $this->assertTrue(true);
    }

    public function test_create_directory_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('putObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-dir/',
                'Body' => '',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $config = new Config;
        $this->adapter->createDirectory('test-dir', $config);

        // If we get here without exception, the test passes
        $this->assertTrue(true);
    }

    public function test_set_visibility_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('setObjectAcl')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
                'ACL' => 'public-read',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $this->adapter->setVisibility('test-file.txt', 'public');

        // If we get here without exception, the test passes
        $this->assertTrue(true);
    }

    public function test_visibility_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('getObjectAcl')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([
                'Grants' => [
                    [
                        'Grantee' => [
                            'URI' => 'http://acs.amazonaws.com/groups/global/AllUsers',
                        ],
                        'Permission' => 'READ',
                    ],
                ],
            ]);

        $result = $this->adapter->visibility('test-file.txt');

        $this->assertInstanceOf(FileAttributes::class, $result);
        $this->assertEquals('public', $result->visibility());
    }

    public function test_mime_type_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([
                'ContentType' => 'text/plain',
            ]);

        $result = $this->adapter->mimeType('test-file.txt');

        $this->assertInstanceOf(FileAttributes::class, $result);
        $this->assertEquals('text/plain', $result->mimeType());
    }

    public function test_last_modified_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([
                'LastModified' => '2023-01-01T12:00:00Z',
            ]);

        $result = $this->adapter->lastModified('test-file.txt');

        $this->assertInstanceOf(FileAttributes::class, $result);
        $this->assertEquals(strtotime('2023-01-01T12:00:00Z'), $result->lastModified());
    }

    public function test_file_size_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([
                'ContentLength' => 1024,
            ]);

        $result = $this->adapter->fileSize('test-file.txt');

        $this->assertInstanceOf(FileAttributes::class, $result);
        $this->assertEquals(1024, $result->fileSize());
    }

    public function test_list_contents_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => 'uploads/',
                'Delimiter' => '/',
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'uploads/file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                    ],
                ],
            ]);

        $contents = iterator_to_array($this->adapter->listContents('uploads', false));

        $this->assertCount(1, $contents);
        $this->assertInstanceOf(FileAttributes::class, $contents[0]);
        $this->assertEquals('uploads/file1.txt', $contents[0]->path());
    }

    public function test_move_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('copyObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'destination.txt',
                'CopySource' => $this->bucket.'/source.txt',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $this->mockClient->shouldReceive('deleteObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'source.txt',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 204]);

        $config = new Config;
        $this->adapter->move('source.txt', 'destination.txt', $config);

        // If we get here without exception, the test passes
        $this->assertTrue(true);
    }

    public function test_copy_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('copyObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'destination.txt',
                'CopySource' => $this->bucket.'/source.txt',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $config = new Config;
        $this->adapter->copy('source.txt', 'destination.txt', $config);

        // If we get here without exception, the test passes
        $this->assertTrue(true);
    }

    // Test additional Laravel compatibility methods

    public function test_url_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('getObjectAcl')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([
                'Grants' => [
                    [
                        'Grantee' => [
                            'URI' => 'http://acs.amazonaws.com/groups/global/AllUsers',
                        ],
                        'Permission' => 'READ',
                    ],
                ],
            ]);

        $this->mockClient->shouldReceive('getConfig')
            ->once()
            ->andReturn(['endpoint' => 'https://obs.test.com']);

        $url = $this->adapter->url('test-file.txt');

        $this->assertIsString($url);
        $this->assertEquals('https://obs.test.com/test-bucket/test-file.txt', $url);
    }

    public function test_get_url_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('getObjectAcl')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([
                'Grants' => [
                    [
                        'Grantee' => [
                            'URI' => 'http://acs.amazonaws.com/groups/global/AllUsers',
                        ],
                        'Permission' => 'READ',
                    ],
                ],
            ]);

        $this->mockClient->shouldReceive('getConfig')
            ->once()
            ->andReturn(['endpoint' => 'https://obs.test.com']);

        $url = $this->adapter->getUrl('test-file.txt');

        $this->assertIsString($url);
        $this->assertEquals('https://obs.test.com/test-bucket/test-file.txt', $url);
    }

    public function test_get_temporary_url_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('createSignedUrl')
            ->with([
                'Method' => 'GET',
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
                'Expires' => 3600,
                'Headers' => [],
            ])
            ->once()
            ->andReturn([
                'SignedUrl' => 'https://signed-url.com/test-file.txt',
            ]);

        $expiration = new \DateTime('+1 hour');
        $url = $this->adapter->getTemporaryUrl('test-file.txt', $expiration);

        $this->assertIsString($url);
        $this->assertEquals('https://signed-url.com/test-file.txt', $url);
    }

    public function test_temporary_upload_url_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('createSignedUrl')
            ->with([
                'Method' => 'PUT',
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
                'Expires' => 3600,
                'Headers' => [],
            ])
            ->once()
            ->andReturn([
                'SignedUrl' => 'https://signed-url.com/test-file.txt',
            ]);

        $expiration = new \DateTime('+1 hour');
        $url = $this->adapter->temporaryUploadUrl('test-file.txt', $expiration);

        $this->assertIsString($url);
        $this->assertEquals('https://signed-url.com/test-file.txt', $url);
    }

    public function test_all_files_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ],
                    [
                        'Key' => 'file2.txt',
                        'Size' => 200,
                        'LastModified' => '2023-01-02T00:00:00Z',
                        'Grants' => [],
                    ],
                ],
            ]);

        $files = $this->adapter->allFiles();

        $this->assertIsArray($files);
        $this->assertCount(2, $files);
        $this->assertContains('file1.txt', $files);
        $this->assertContains('file2.txt', $files);
    }

    public function test_all_directories_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'CommonPrefixes' => [
                    ['Prefix' => 'dir1/'],
                    ['Prefix' => 'dir2/'],
                ],
            ]);

        $directories = $this->adapter->allDirectories();

        $this->assertIsArray($directories);
        $this->assertCount(2, $directories);
        $this->assertContains('dir1', $directories);
        $this->assertContains('dir2', $directories);
    }

    // Test error handling

    public function test_size_method_throws_exception_on_error(): void
    {
        $exception = new ObsException('AccessDenied');
        $exception->setExceptionCode('AccessDenied');

        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andThrow($exception);

        $this->expectException(\League\Flysystem\UnableToRetrieveMetadata::class);

        $this->adapter->size('test-file.txt');
    }

    public function test_get_last_modified_method_throws_exception_on_error(): void
    {
        $exception = new ObsException('AccessDenied');
        $exception->setExceptionCode('AccessDenied');

        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andThrow($exception);

        $this->expectException(\League\Flysystem\UnableToRetrieveMetadata::class);

        $this->adapter->getLastModified('test-file.txt');
    }

    public function test_get_mime_type_method_throws_exception_on_error(): void
    {
        $exception = new ObsException('AccessDenied');
        $exception->setExceptionCode('AccessDenied');

        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andThrow($exception);

        $this->expectException(\League\Flysystem\UnableToRetrieveMetadata::class);

        $this->adapter->getMimeType('test-file.txt');
    }

    public function test_get_visibility_method_throws_exception_on_error(): void
    {
        $this->mockClient->shouldReceive('getObjectAcl')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andThrow(new ObsException('AccessDenied'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to retrieve the visibility for file at location: test-file.txt. AccessDenied');

        $this->adapter->getVisibility('test-file.txt');
    }

    // ============================================================================
    // TESTS FOR NEW OPTIMIZED METHODS IN LARAVEL ADAPTER
    // ============================================================================

    public function test_all_files_optimized_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 100,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ],
                    [
                        'Key' => 'file2.jpg',
                        'Size' => 200,
                        'LastModified' => '2023-01-02T00:00:00Z',
                        'Grants' => [],
                    ],
                ],
            ]);

        $files = $this->adapter->allFilesOptimized(100, 30);

        $this->assertCount(2, $files);
        $this->assertContains('file1.txt', $files);
        $this->assertContains('file2.jpg', $files);
    }

    public function test_all_directories_optimized_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 100,
            ])
            ->once()
            ->andReturn([
                'CommonPrefixes' => [
                    ['Prefix' => 'directory1/'],
                    ['Prefix' => 'directory2/'],
                ],
            ]);

        $directories = $this->adapter->allDirectoriesOptimized(100, 30);

        $this->assertCount(2, $directories);
        $this->assertContains('directory1', $directories);
        $this->assertContains('directory2', $directories);
    }

    public function test_get_storage_stats_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ],
                    [
                        'Key' => 'file2.jpg',
                        'Size' => 200,
                        'LastModified' => '2023-01-02T00:00:00Z',
                        'Grants' => [],
                    ],
                ],
                'CommonPrefixes' => [
                    ['Prefix' => 'directory1/'],
                ],
            ]);

        $stats = $this->adapter->getStorageStats();

        $this->assertEquals(2, $stats['total_files']);
        $this->assertEquals(1, $stats['total_directories']);
        $this->assertEquals(300, $stats['total_size_bytes']);
        $this->assertEquals(['txt' => 1, 'jpg' => 1], $stats['file_types']);
        $this->assertEquals(3, $stats['processed_count']);
        $this->assertArrayHasKey('processing_time_seconds', $stats);
        $this->assertFalse($stats['has_more_files']);
    }

    public function test_list_contents_optimized_delegates_to_base_adapter(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => 'test/',
                'Delimiter' => null,
                'MaxKeys' => 500,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'test/file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                    ],
                ],
            ]);

        $contents = iterator_to_array($this->adapter->listContentsOptimized('test', true, 500, 30));

        $this->assertCount(1, $contents);
        $this->assertInstanceOf(\League\Flysystem\FileAttributes::class, $contents[0]);
        $this->assertEquals('test/file1.txt', $contents[0]->path()); // Full path is returned
    }

    // ============================================================================
    // EDGE CASES FOR LARAVEL ADAPTER
    // ============================================================================

    public function test_all_files_optimized_with_zero_limits(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000, // Should use default when maxKeys is 0
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                    ],
                ],
            ]);

        $files = $this->adapter->allFilesOptimized(0, 0);

        $this->assertCount(1, $files);
        $this->assertContains('file1.txt', $files);
    }

    public function test_get_storage_stats_with_custom_limits(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 500,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                    ],
                ],
            ]);

        $stats = $this->adapter->getStorageStats(500, 10);

        $this->assertEquals(1, $stats['total_files']);
        $this->assertEquals(100, $stats['total_size_bytes']);
        $this->assertFalse($stats['has_more_files']); // Only 1 file processed, not reaching limit
    }

    public function test_list_contents_optimized_with_empty_response(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => 'empty/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([]);

        $contents = iterator_to_array($this->adapter->listContentsOptimized('empty', true));

        $this->assertCount(0, $contents);
    }

    public function test_all_files_optimized_handles_api_errors(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 100,
            ])
            ->once()
            ->andThrow(new ObsException('API Error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to list contents: API Error');

        $this->adapter->allFilesOptimized(100, 30);
    }

    public function test_get_storage_stats_handles_api_errors(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andThrow(new ObsException('AccessDenied'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to list contents: AccessDenied');

        $this->adapter->getStorageStats();
    }

    public function test_list_contents_optimized_handles_authentication_errors(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => 'test/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andThrow(new ObsException('InvalidAccessKeyId'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to list contents: InvalidAccessKeyId');

        iterator_to_array($this->adapter->listContentsOptimized('test', true));
    }

    // ============================================================================
    // PERFORMANCE TESTS FOR LARAVEL ADAPTER
    // ============================================================================

    public function test_all_files_optimized_performance(): void
    {
        $startTime = microtime(true);

        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => array_map(function ($i) {
                    return [
                        'Key' => "file{$i}.txt",
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                    ];
                }, range(1, 1000)),
            ]);

        $files = $this->adapter->allFilesOptimized(1000, 60);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertCount(1000, $files);
        // Should complete within 1 second for 1000 files
        $this->assertLessThan(1.0, $executionTime);
    }

    public function test_get_storage_stats_performance(): void
    {
        $startTime = microtime(true);

        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => array_map(function ($i) {
                    return [
                        'Key' => "file{$i}.txt",
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                    ];
                }, range(1, 1000)),
            ]);

        $stats = $this->adapter->getStorageStats(1000, 60);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertEquals(1000, $stats['total_files']);
        $this->assertEquals(100000, $stats['total_size_bytes']);
        // Should complete within 1 second for 1000 files
        $this->assertLessThan(1.0, $executionTime);
    }

    // ============================================================================
    // INTEGRATION TESTS - REAL-WORLD SCENARIOS
    // ============================================================================

    public function test_complete_file_browser_scenario(): void
    {
        // Simulate a real file browser scenario
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => 'uploads/',
                'Delimiter' => '/',
                'MaxKeys' => 1000,
            ])
            ->twice() // Called twice: once for files/directories, once for getStorageStats
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'uploads/file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ],
                    [
                        'Key' => 'uploads/file2.jpg',
                        'Size' => 200,
                        'LastModified' => '2023-01-02T00:00:00Z',
                        'Grants' => [],
                    ],
                ],
                'CommonPrefixes' => [
                    ['Prefix' => 'uploads/subdir/'],
                ],
            ]);

        // Add mock for getStorageStats call
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'uploads/file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                    ],
                    [
                        'Key' => 'uploads/file2.jpg',
                        'Size' => 200,
                        'LastModified' => '2023-01-02T00:00:00Z',
                    ],
                ],
                'CommonPrefixes' => [
                    ['Prefix' => 'uploads/subdir/'],
                ],
            ]);

        // Test files method
        $files = $this->adapter->files('uploads');
        $this->assertCount(2, $files);
        $this->assertContains('uploads/file1.txt', $files);
        $this->assertContains('uploads/file2.jpg', $files);

        // Test directories method
        $directories = $this->adapter->directories('uploads');
        $this->assertCount(1, $directories);
        $this->assertContains('uploads/subdir', $directories);

        // Test optimized methods
        $stats = $this->adapter->getStorageStats(1000, 30);
        $this->assertEquals(2, $stats['total_files']);
        $this->assertEquals(1, $stats['total_directories']);
        $this->assertEquals(300, $stats['total_size_bytes']);
    }

    public function test_large_dataset_processing_scenario(): void
    {
        // Simulate processing a large dataset with limits
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 100,
            ])
            ->twice() // Called twice: once for allFilesOptimized, once for getStorageStats
            ->andReturn([
                'Contents' => array_map(function ($i) {
                    return [
                        'Key' => "large-dataset/file{$i}.txt",
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                    ];
                }, range(1, 100)),
            ]);

        // Test with limits to prevent timeouts
        $files = $this->adapter->allFilesOptimized(100, 10);
        $this->assertCount(100, $files);

        // Test storage stats with limits
        $stats = $this->adapter->getStorageStats(100, 10);
        $this->assertEquals(100, $stats['total_files']);
        $this->assertEquals(10000, $stats['total_size_bytes']);
        $this->assertTrue($stats['has_more_files']); // Indicates there might be more files
    }

    public function test_error_recovery_scenario(): void
    {
        // Test that the adapter gracefully handles errors and provides useful information
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andThrow(new ObsException('NoSuchBucket'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to list contents: NoSuchBucket');

        $this->adapter->getStorageStats();
    }
}
