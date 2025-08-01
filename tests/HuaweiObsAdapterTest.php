<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests;

use GuzzleHttp\Client;
use LaravelFlysystemHuaweiObs\HuaweiObsAdapter;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use Mockery;
use Obs\ObsClient;
use Obs\ObsException;
use PHPUnit\Framework\TestCase;

class HuaweiObsAdapterTest extends TestCase
{
    private string $accessKeyId = 'test-key';

    private string $secretAccessKey = 'test-secret';

    private string $bucket = 'test-bucket';

    private string $endpoint = 'https://obs.test.com';

    /** @var \Mockery\MockInterface&\Obs\ObsClient */
    private $mockClient;

    private HuaweiObsAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = Mockery::mock(ObsClient::class);

        // Mock authentication check
        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andReturn(['HttpStatusCode' => 200])
            ->byDefault();

        $this->adapter = new HuaweiObsAdapter(
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

        // Replace the client with our mock
        $reflection = new \ReflectionClass($this->adapter);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->adapter, $this->mockClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_file_exists_returns_true_when_file_exists(): void
    {
        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $this->assertTrue($this->adapter->fileExists('test-file.txt'));
    }

    public function test_file_exists_returns_false_when_file_does_not_exist(): void
    {
        $exception = new ObsException('NoSuchResource');
        $exception->setExceptionCode('NoSuchResource');

        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andThrow($exception);

        $this->assertFalse($this->adapter->fileExists('test-file.txt'));
    }

    public function test_file_exists_throws_exception_on_other_errors(): void
    {
        $exception = new ObsException('Network error');

        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andThrow($exception);

        $this->expectException(UnableToCheckFileExistence::class);
        $this->adapter->fileExists('test-file.txt');
    }

    public function test_directory_exists_returns_true_when_directory_exists(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => 'test-directory/',
                'MaxKeys' => 1,
            ])
            ->once()
            ->andReturn(['Contents' => [['Key' => 'test-directory/file.txt']]]);

        $this->assertTrue($this->adapter->directoryExists('test-directory'));
    }

    public function test_directory_exists_returns_false_when_directory_does_not_exist(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => 'test-directory/',
                'MaxKeys' => 1,
            ])
            ->once()
            ->andReturn(['Contents' => []]);

        $this->assertFalse($this->adapter->directoryExists('test-directory'));
    }

    public function test_write_file_successfully(): void
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

        $this->adapter->write('test-file.txt', 'test content', new Config);

        $this->assertTrue(true); // Assertion to avoid risky test
    }

    public function test_write_file_with_public_visibility(): void
    {
        $this->mockClient->shouldReceive('putObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
                'Body' => 'test content',
                'ACL' => 'public-read',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $this->adapter->write('test-file.txt', 'test content', new Config(['visibility' => Visibility::PUBLIC]));

        $this->assertTrue(true); // Assertion to avoid risky test
    }

    public function test_write_file_with_mime_type(): void
    {
        $this->mockClient->shouldReceive('putObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
                'Body' => 'test content',
                'ACL' => 'private',
                'ContentType' => 'text/plain',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $this->adapter->write('test-file.txt', 'test content', new Config(['mimetype' => 'text/plain']));

        $this->assertTrue(true); // Assertion to avoid risky test
    }

    public function test_write_file_throws_exception_on_error(): void
    {
        $exception = new ObsException('Write failed');

        $this->mockClient->shouldReceive('putObject')
            ->once()
            ->andThrow($exception);

        $this->expectException(UnableToWriteFile::class);
        $this->adapter->write('test-file.txt', 'test content', new Config);
    }

    public function test_write_stream_successfully(): void
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            $this->fail('Failed to create temporary stream');
        }
        fwrite($stream, 'test content');
        rewind($stream);

        $this->mockClient->shouldReceive('putObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
                'Body' => $stream,
                'ACL' => 'private',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $this->adapter->writeStream('test-file.txt', $stream, new Config);

        $this->assertTrue(true); // Assertion to avoid risky test
    }

    public function test_read_file_successfully(): void
    {
        $this->mockClient->shouldReceive('getObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn(['Body' => 'test content']);

        $result = $this->adapter->read('test-file.txt');
        $this->assertEquals('test content', $result);
    }

    public function test_read_file_throws_exception_on_error(): void
    {
        $exception = new ObsException('Read failed');

        $this->mockClient->shouldReceive('getObject')
            ->once()
            ->andThrow($exception);

        $this->expectException(UnableToReadFile::class);
        $this->adapter->read('test-file.txt');
    }

    public function test_read_stream_successfully(): void
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            $this->fail('Failed to create temporary stream');
        }
        fwrite($stream, 'test content');
        rewind($stream);

        $this->mockClient->shouldReceive('getObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn(['Body' => 'test content']);

        $result = $this->adapter->readStream('test-file.txt');

        $this->assertIsResource($result);
        $this->assertEquals('test content', stream_get_contents($result));
        fclose($result);
    }

    public function test_delete_file_successfully(): void
    {
        $this->mockClient->shouldReceive('deleteObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $this->adapter->delete('test-file.txt');

        $this->assertTrue(true); // Assertion to avoid risky test
    }

    public function test_delete_file_throws_exception_on_error(): void
    {
        $exception = new ObsException('Delete failed');

        $this->mockClient->shouldReceive('deleteObject')
            ->once()
            ->andThrow($exception);

        $this->expectException(UnableToDeleteFile::class);
        $this->adapter->delete('test-file.txt');
    }

    public function test_delete_directory_successfully(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => 'test-directory/',
                'Marker' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    ['Key' => 'test-directory/file1.txt'],
                    ['Key' => 'test-directory/file2.txt'],
                ],
                'IsTruncated' => false,
            ]);

        $this->mockClient->shouldReceive('deleteObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Objects' => [
                    ['Key' => 'test-directory/file1.txt'],
                    ['Key' => 'test-directory/file2.txt'],
                ],
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $this->adapter->deleteDirectory('test-directory');

        $this->assertTrue(true); // Assertion to avoid risky test
    }

    public function test_delete_directory_throws_exception_on_error(): void
    {
        $exception = new ObsException('Delete directory failed');

        $this->mockClient->shouldReceive('listObjects')
            ->once()
            ->andThrow($exception);

        $this->expectException(UnableToDeleteDirectory::class);
        $this->adapter->deleteDirectory('test-directory');
    }

    public function test_create_directory_successfully(): void
    {
        $this->mockClient->shouldReceive('putObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-directory/',
                'Body' => '',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $this->adapter->createDirectory('test-directory', new Config);

        $this->assertTrue(true); // Assertion to avoid risky test
    }

    public function test_create_directory_throws_exception_on_error(): void
    {
        $exception = new ObsException('Create directory failed');

        $this->mockClient->shouldReceive('putObject')
            ->once()
            ->andThrow($exception);

        $this->expectException(UnableToCreateDirectory::class);
        $this->adapter->createDirectory('test-directory', new Config);
    }

    public function test_set_visibility_successfully(): void
    {
        $this->mockClient->shouldReceive('setObjectAcl')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
                'ACL' => 'public-read',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $this->adapter->setVisibility('test-file.txt', Visibility::PUBLIC);

        $this->assertTrue(true); // Assertion to avoid risky test
    }

    public function test_set_visibility_throws_exception_on_error(): void
    {
        $exception = new ObsException('Set visibility failed');

        $this->mockClient->shouldReceive('setObjectAcl')
            ->once()
            ->andThrow($exception);

        $this->expectException(UnableToSetVisibility::class);
        $this->adapter->setVisibility('test-file.txt', Visibility::PUBLIC);
    }

    public function test_visibility_returns_public_when_public_read(): void
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
                        'Grantee' => ['URI' => 'http://acs.amazonaws.com/groups/global/AllUsers'],
                        'Permission' => 'READ',
                    ],
                ],
            ]);

        $result = $this->adapter->visibility('test-file.txt');
        $this->assertInstanceOf(FileAttributes::class, $result);
        $this->assertEquals(Visibility::PUBLIC, $result->visibility());
    }

    public function test_visibility_returns_private_when_no_public_access(): void
    {
        $this->mockClient->shouldReceive('getObjectAcl')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn(['Grants' => []]);

        $result = $this->adapter->visibility('test-file.txt');
        $this->assertInstanceOf(FileAttributes::class, $result);
        $this->assertEquals(Visibility::PRIVATE, $result->visibility());
    }

    public function test_mime_type_returns_correct_mime_type(): void
    {
        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn(['ContentType' => 'text/plain']);

        $result = $this->adapter->mimeType('test-file.txt');
        $this->assertInstanceOf(FileAttributes::class, $result);
        $this->assertEquals('text/plain', $result->mimeType());
    }

    public function test_last_modified_returns_correct_timestamp(): void
    {
        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn(['LastModified' => '2023-01-01T00:00:00Z']);

        $result = $this->adapter->lastModified('test-file.txt');
        $this->assertInstanceOf(FileAttributes::class, $result);
        $this->assertEquals(strtotime('2023-01-01T00:00:00Z'), $result->lastModified());
    }

    public function test_file_size_returns_correct_size(): void
    {
        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn(['ContentLength' => 1024]);

        $result = $this->adapter->fileSize('test-file.txt');
        $this->assertInstanceOf(FileAttributes::class, $result);
        $this->assertEquals(1024, $result->fileSize());
    }

    public function test_move_file_successfully(): void
    {
        $this->mockClient->shouldReceive('copyObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'new-location.txt',
                'CopySource' => $this->bucket.'/old-location.txt',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $this->mockClient->shouldReceive('deleteObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'old-location.txt',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $this->adapter->move('old-location.txt', 'new-location.txt', new Config);

        $this->assertTrue(true); // Assertion to avoid risky test
    }

    public function test_copy_file_successfully(): void
    {
        $this->mockClient->shouldReceive('copyObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'destination.txt',
                'CopySource' => $this->bucket.'/source.txt',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $this->adapter->copy('source.txt', 'destination.txt', new Config);

        $this->assertTrue(true); // Assertion to avoid risky test
    }

    public function test_list_contents_returns_files_and_directories(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => 'test-directory/',
                'Delimiter' => '/',
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'test-directory/file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ],
                ],
                'CommonPrefixes' => [
                    ['Prefix' => 'test-directory/subdir/'],
                ],
            ]);

        $contents = iterator_to_array($this->adapter->listContents('test-directory', false));

        $this->assertCount(2, $contents);
        $this->assertInstanceOf(FileAttributes::class, $contents[0]);
        $this->assertInstanceOf(DirectoryAttributes::class, $contents[1]);
    }

    public function test_adapter_with_prefix(): void
    {
        $adapter = new HuaweiObsAdapter(
            $this->accessKeyId,
            $this->secretAccessKey,
            $this->bucket,
            $this->endpoint,
            'prefix/',
            null,
            null,
            3,
            1,
            false,
            false,
            true
        );

        // Replace with mock client
        $reflection = new \ReflectionClass($adapter);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($adapter, $this->mockClient);

        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andReturn(['HttpStatusCode' => 200]);

        $this->mockClient->shouldReceive('putObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'prefix//test-file.txt',
                'Body' => 'test content',
                'ACL' => 'private',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $adapter->write('test-file.txt', 'test content', new Config);

        $this->assertTrue(true); // Assertion to avoid risky test
    }

    public function test_create_signed_url(): void
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
            ->andReturn(['SignedUrl' => 'https://signed-url.example.com']);

        $signedUrl = $this->adapter->createSignedUrl('test-file.txt');
        $this->assertEquals('https://signed-url.example.com', $signedUrl);
    }

    public function test_create_signed_url_with_custom_method_and_expires(): void
    {
        $this->mockClient->shouldReceive('createSignedUrl')
            ->with([
                'Method' => 'PUT',
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
                'Expires' => 7200,
                'Headers' => ['Content-Type' => 'text/plain'],
            ])
            ->once()
            ->andReturn(['SignedUrl' => 'https://signed-url.example.com']);

        $signedUrl = $this->adapter->createSignedUrl('test-file.txt', 'PUT', 7200, ['Content-Type' => 'text/plain']);
        $this->assertEquals('https://signed-url.example.com', $signedUrl);
    }

    public function test_url_returns_public_url_for_public_object(): void
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
            ->andReturn(['endpoint' => 'https://obs.cn-north-1.myhuaweicloud.com']);

        $url = $this->adapter->url('test-file.txt');
        $this->assertEquals('https://obs.cn-north-1.myhuaweicloud.com/test-bucket/test-file.txt', $url);
    }

    public function test_url_throws_exception_for_private_object(): void
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
                            'ID' => 'test-user-id',
                        ],
                        'Permission' => 'READ',
                    ],
                ],
            ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This driver does not support retrieving URLs for private objects. Use createSignedUrl() for temporary access.');

        $this->adapter->url('test-file.txt');
    }

    public function test_url_throws_exception_for_nonexistent_file(): void
    {
        $this->mockClient->shouldReceive('getObjectAcl')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'nonexistent-file.txt',
            ])
            ->once()
            ->andThrow(new \Obs\ObsException('NoSuchResource'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to retrieve URL: NoSuchResource');

        $this->adapter->url('nonexistent-file.txt');
    }

    public function test_url_with_prefix(): void
    {
        $adapter = new HuaweiObsAdapter(
            'test-key',
            'test-secret',
            'test-bucket',
            'https://obs.cn-north-1.myhuaweicloud.com',
            'uploads'
        );

        $this->mockClient = \Mockery::mock(\Obs\ObsClient::class);
        $adapterReflection = new \ReflectionClass($adapter);
        $clientProperty = $adapterReflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($adapter, $this->mockClient);

        // Mock authentication check
        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => 'test-bucket'])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $this->mockClient->shouldReceive('getObjectAcl')
            ->with([
                'Bucket' => 'test-bucket',
                'Key' => 'uploads/test-file.txt',
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
            ->andReturn(['endpoint' => 'https://obs.cn-north-1.myhuaweicloud.com']);

        $url = $adapter->url('test-file.txt');
        $this->assertEquals('https://obs.cn-north-1.myhuaweicloud.com/test-bucket/uploads/test-file.txt', $url);
    }

    public function test_all_files(): void
    {
        // Mock authentication check
        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

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
                    ],
                    [
                        'Key' => 'folder/file2.txt',
                        'Size' => 200,
                        'LastModified' => '2023-01-02T00:00:00Z',
                    ],
                ],
            ]);

        $files = $this->adapter->allFiles();
        $this->assertEquals(['file1.txt', 'folder/file2.txt'], $files);
    }

    public function test_all_directories(): void
    {
        // Mock authentication check
        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

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
                    ['Prefix' => 'folder1/'],
                    ['Prefix' => 'folder2/'],
                ],
            ]);

        $directories = $this->adapter->allDirectories();
        $this->assertEquals(['folder1', 'folder2'], $directories);
    }

    public function test_get_visibility(): void
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
        $this->assertEquals('public', $visibility);
    }

    public function test_create_post_signature(): void
    {
        $this->mockClient->shouldReceive('createPostSignature')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
                'Expires' => 3600,
                'Conditions' => [],
            ])
            ->once()
            ->andReturn(['Policy' => 'test-policy', 'Signature' => 'test-signature']);

        $result = $this->adapter->createPostSignature('test-file.txt');
        $this->assertEquals(['Policy' => 'test-policy', 'Signature' => 'test-signature'], $result);
    }

    public function test_set_object_tags(): void
    {
        $this->mockClient->shouldReceive('setObjectTagging')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
                'TagSet' => ['environment' => 'production', 'type' => 'image'],
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $this->adapter->setObjectTags('test-file.txt', ['environment' => 'production', 'type' => 'image']);

        $this->assertTrue(true); // Assertion to avoid risky test
    }

    public function test_get_object_tags(): void
    {
        $this->mockClient->shouldReceive('getObjectTagging')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([
                'TagSet' => [
                    ['Key' => 'environment', 'Value' => 'production'],
                    ['Key' => 'type', 'Value' => 'image'],
                ],
            ]);

        $result = $this->adapter->getObjectTags('test-file.txt');

        $this->assertEquals([
            ['Key' => 'environment', 'Value' => 'production'],
            ['Key' => 'type', 'Value' => 'image'],
        ], $result);
    }

    public function test_delete_object_tags(): void
    {
        $this->mockClient->shouldReceive('deleteObjectTagging')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $this->adapter->deleteObjectTags('test-file.txt');

        $this->assertTrue(true); // Assertion to avoid risky test
    }

    public function test_restore_object(): void
    {
        $this->mockClient->shouldReceive('restoreObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
                'Days' => 7,
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $this->adapter->restoreObject('test-file.txt', 7);

        $this->assertTrue(true); // Assertion to avoid risky test
    }

    public function test_refresh_credentials(): void
    {
        $this->mockClient->shouldReceive('refresh')
            ->with('new-key', 'new-secret', 'new-token')
            ->once();

        $this->adapter->refreshCredentials('new-key', 'new-secret', 'new-token');

        $this->assertTrue(true); // Assertion to avoid risky test
    }

    public function test_refresh_credentials_without_token(): void
    {
        $this->mockClient->shouldReceive('refresh')
            ->with('new-key', 'new-secret', null)
            ->once();

        $this->adapter->refreshCredentials('new-key', 'new-secret');

        $this->assertTrue(true); // Assertion to avoid risky test
    }

    public function test_refresh_authentication_clears_cache(): void
    {
        // First call should cache authentication
        $this->adapter->refreshAuthentication();

        // Second call should work without hitting the API again
        $this->adapter->refreshAuthentication();

        // Verify headBucket was called twice (once for each refresh)
        $this->mockClient->shouldHaveReceived('headBucket')->twice();

        $this->assertTrue(true); // Assertion to avoid risky test
    }

    public function test_retry_logic_on_transient_errors(): void
    {
        // Create a new adapter with retry logic for this test
        $adapter = new HuaweiObsAdapter(
            $this->accessKeyId,
            $this->secretAccessKey,
            $this->bucket,
            $this->endpoint,
            null,
            null,
            null,
            2, // retryAttempts
            0, // retryDelay (no delay for testing)
            false,
            false,
            true
        );

        // Replace with mock client
        $reflection = new \ReflectionClass($adapter);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($adapter, $this->mockClient);

        // Mock authentication check
        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andReturn(['HttpStatusCode' => 200]);

        // Mock a transient error followed by success for createSignedUrl
        $this->mockClient->shouldReceive('createSignedUrl')
            ->with([
                'Method' => 'GET',
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
                'Expires' => 3600,
                'Headers' => [],
            ])
            ->once()
            ->andThrow(new ObsException('Transient error'))
            ->ordered();

        $this->mockClient->shouldReceive('createSignedUrl')
            ->with([
                'Method' => 'GET',
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
                'Expires' => 3600,
                'Headers' => [],
            ])
            ->once()
            ->andReturn(['SignedUrl' => 'https://example.com/signed-url'])
            ->ordered();

        $result = $adapter->createSignedUrl('test-file.txt');

        $this->assertEquals('https://example.com/signed-url', $result);
    }

    public function test_retry_logic_does_not_retry_authentication_errors(): void
    {
        // Create a new adapter for this test
        $adapter = new HuaweiObsAdapter(
            $this->accessKeyId,
            $this->secretAccessKey,
            $this->bucket,
            $this->endpoint,
            null,
            null,
            null,
            2, // retryAttempts
            0, // retryDelay
            false,
            false,
            true
        );

        // Replace with mock client
        $reflection = new \ReflectionClass($adapter);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($adapter, $this->mockClient);

        // Mock an authentication error for the authentication check
        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andThrow(new ObsException('AccessDenied'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to create signed URL for location: test-file.txt');

        $adapter->createSignedUrl('test-file.txt');
    }

    public function test_logging_enabled_with_operations(): void
    {
        $adapter = new HuaweiObsAdapter(
            $this->accessKeyId,
            $this->secretAccessKey,
            $this->bucket,
            $this->endpoint,
            null,
            null,
            null,
            3,
            1,
            true, // loggingEnabled
            true, // logOperations
            true  // logErrors
        );

        // Replace the client with our mock
        $reflection = new \ReflectionClass($adapter);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($adapter, $this->mockClient);

        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andReturn(['HttpStatusCode' => 200]);

        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        // Test that logging doesn't interfere with normal operations
        $this->assertTrue($adapter->fileExists('test-file.txt'));
    }

    public function test_logging_enabled_with_errors(): void
    {
        $adapter = new HuaweiObsAdapter(
            $this->accessKeyId,
            $this->secretAccessKey,
            $this->bucket,
            $this->endpoint,
            null,
            null,
            null,
            3,
            1,
            true, // loggingEnabled
            false, // logOperations
            true   // logErrors
        );

        // Replace the client with our mock
        $reflection = new \ReflectionClass($adapter);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($adapter, $this->mockClient);

        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andReturn(['HttpStatusCode' => 200]);

        $exception = new ObsException('Test error');
        $exception->setExceptionCode('TestError');

        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andThrow($exception);

        // Test that error logging doesn't interfere with exception handling
        $this->expectException(UnableToCheckFileExistence::class);
        $adapter->fileExists('test-file.txt');
    }

    public function test_log_operation_method_coverage(): void
    {
        $adapter = new HuaweiObsAdapter(
            $this->accessKeyId,
            $this->secretAccessKey,
            $this->bucket,
            $this->endpoint,
            null,
            null,
            null,
            3,
            1,
            true, // loggingEnabled
            true, // logOperations
            true  // logErrors
        );

        // Replace the client with our mock
        $reflection = new \ReflectionClass($adapter);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($adapter, $this->mockClient);

        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andReturn(['HttpStatusCode' => 200]);

        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        // This will trigger logOperation internally
        $adapter->fileExists('test-file.txt');

        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    public function test_log_error_method_coverage(): void
    {
        $adapter = new HuaweiObsAdapter(
            $this->accessKeyId,
            $this->secretAccessKey,
            $this->bucket,
            $this->endpoint,
            null,
            null,
            null,
            3,
            1,
            true, // loggingEnabled
            false, // logOperations
            true   // logErrors
        );

        // Replace the client with our mock
        $reflection = new \ReflectionClass($adapter);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($adapter, $this->mockClient);

        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andReturn(['HttpStatusCode' => 200]);

        $exception = new ObsException('Test error');
        $exception->setExceptionCode('TestError');

        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andThrow($exception);

        // This will trigger logError internally
        $this->expectException(UnableToCheckFileExistence::class);
        $adapter->fileExists('test-file.txt');
    }

    public function test_visibility_with_invalid_grants(): void
    {
        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andReturn(['HttpStatusCode' => 200]);

        $this->mockClient->shouldReceive('getObjectAcl')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([
                'Grants' => [
                    ['Grantee' => ['URI' => 'invalid-uri'], 'Permission' => 'READ'],
                ],
            ]);

        $result = $this->adapter->visibility('test-file.txt');
        $this->assertInstanceOf(FileAttributes::class, $result);
        $this->assertEquals(Visibility::PRIVATE, $result->visibility());
    }

    public function test_mime_type_with_missing_content_type(): void
    {
        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andReturn(['HttpStatusCode' => 200]);

        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([
                'HttpStatusCode' => 200,
                // Missing ContentType
            ]);

        $result = $this->adapter->mimeType('test-file.txt');
        $this->assertInstanceOf(FileAttributes::class, $result);
        $this->assertEquals('application/octet-stream', $result->mimeType());
    }

    public function test_last_modified_with_missing_date(): void
    {
        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andReturn(['HttpStatusCode' => 200]);

        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([
                'HttpStatusCode' => 200,
                // Missing LastModified
            ]);

        $result = $this->adapter->lastModified('test-file.txt');
        $this->assertInstanceOf(FileAttributes::class, $result);
        $this->assertIsInt($result->lastModified());
    }

    public function test_file_size_with_missing_size(): void
    {
        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andReturn(['HttpStatusCode' => 200]);

        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->once()
            ->andReturn([
                'HttpStatusCode' => 200,
                // Missing ContentLength
            ]);

        $result = $this->adapter->fileSize('test-file.txt');
        $this->assertInstanceOf(FileAttributes::class, $result);
        $this->assertEquals(0, $result->fileSize());
    }

    public function test_move_with_destination_exists(): void
    {
        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andReturn(['HttpStatusCode' => 200]);

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
            ->andReturn(['HttpStatusCode' => 200]);

        $this->adapter->move('source.txt', 'destination.txt', new Config);
        $this->assertTrue(true); // Assert that no exception was thrown
    }

    public function test_copy_with_error(): void
    {
        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andReturn(['HttpStatusCode' => 200]);

        $exception = new ObsException('Copy failed');
        $exception->setExceptionCode('CopyError');

        $this->mockClient->shouldReceive('copyObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'destination.txt',
                'CopySource' => $this->bucket.'/source.txt',
            ])
            ->once()
            ->andThrow($exception);

        $this->expectException(\League\Flysystem\UnableToCopyFile::class);
        $this->adapter->copy('source.txt', 'destination.txt', new Config);
    }

    public function test_private_helper_methods(): void
    {
        $reflection = new \ReflectionClass($this->adapter);

        // Test getKey method
        $getKeyMethod = $reflection->getMethod('getKey');
        $getKeyMethod->setAccessible(true);
        $this->assertEquals('test-file.txt', $getKeyMethod->invoke($this->adapter, 'test-file.txt'));

        // Test getRelativePath method
        $getRelativePathMethod = $reflection->getMethod('getRelativePath');
        $getRelativePathMethod->setAccessible(true);
        $this->assertEquals('/test-file.txt', $getRelativePathMethod->invoke($this->adapter, 'test-file.txt'));

        // Test visibilityToAcl method
        $visibilityToAclMethod = $reflection->getMethod('visibilityToAcl');
        $visibilityToAclMethod->setAccessible(true);
        $this->assertEquals('public-read', $visibilityToAclMethod->invoke($this->adapter, Visibility::PUBLIC));
        $this->assertEquals('private', $visibilityToAclMethod->invoke($this->adapter, Visibility::PRIVATE));

        // Test aclToVisibility method
        $aclToVisibilityMethod = $reflection->getMethod('aclToVisibility');
        $aclToVisibilityMethod->setAccessible(true);
        $this->assertEquals(Visibility::PUBLIC, $aclToVisibilityMethod->invoke($this->adapter, [
            ['Grantee' => ['URI' => 'http://acs.amazonaws.com/groups/global/AllUsers'], 'Permission' => 'READ'],
        ]));
        $this->assertEquals(Visibility::PRIVATE, $aclToVisibilityMethod->invoke($this->adapter, []));
    }

    public function test_authentication_cache_expiry(): void
    {
        // Create adapter with short cache time
        $adapter = new HuaweiObsAdapter(
            $this->accessKeyId,
            $this->secretAccessKey,
            $this->bucket,
            $this->endpoint,
            null,
            null,
            null,
            3,
            1,
            false,
            false,
            true
        );

        // Replace with mock client
        $reflection = new \ReflectionClass($adapter);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($adapter, $this->mockClient);

        // Mock authentication check - should be called twice due to cache expiry
        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->twice()
            ->andReturn(['HttpStatusCode' => 200]);

        // Mock file existence check
        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->twice()
            ->andReturn(['HttpStatusCode' => 200]);

        // First call - should cache authentication
        $result1 = $adapter->fileExists('test-file.txt');
        $this->assertTrue($result1);

        // Simulate cache expiry by setting authCacheExpiry to past
        $authCacheProperty = $reflection->getProperty('authCacheExpiry');
        $authCacheProperty->setAccessible(true);
        $authCacheProperty->setValue($adapter, time() - 1);

        // Second call - should re-authenticate
        $result2 = $adapter->fileExists('test-file.txt');
        $this->assertTrue($result2);
    }

    public function test_authentication_failure_with_access_denied(): void
    {
        $exception = new ObsException('AccessDenied');
        $exception->setExceptionCode('AccessDenied');

        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andThrow($exception);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Authentication failed. Please check your Huawei OBS credentials');

        $this->adapter->fileExists('test-file.txt');
    }

    public function test_authentication_failure_with_invalid_access_key(): void
    {
        $exception = new ObsException('InvalidAccessKeyId');
        $exception->setExceptionCode('InvalidAccessKeyId');

        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andThrow($exception);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Authentication failed. Please check your Huawei OBS credentials');

        $this->adapter->fileExists('test-file.txt');
    }

    public function test_authentication_failure_with_signature_mismatch(): void
    {
        $exception = new ObsException('SignatureDoesNotMatch');
        $exception->setExceptionCode('SignatureDoesNotMatch');

        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andThrow($exception);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Authentication failed. Please check your Huawei OBS credentials');

        $this->adapter->fileExists('test-file.txt');
    }

    public function test_authentication_failure_with_no_such_bucket(): void
    {
        $exception = new ObsException('NoSuchBucket');
        $exception->setExceptionCode('NoSuchBucket');

        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andThrow($exception);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Bucket '{$this->bucket}' does not exist or you don't have access to it");

        $this->adapter->fileExists('test-file.txt');
    }

    public function test_constructor_with_http_client(): void
    {
        $httpClient = new \GuzzleHttp\Client(['timeout' => 30]);

        $adapter = new HuaweiObsAdapter(
            $this->accessKeyId,
            $this->secretAccessKey,
            $this->bucket,
            $this->endpoint,
            null,
            $httpClient,
            null,
            3,
            1,
            false,
            false,
            true
        );

        $this->assertInstanceOf(HuaweiObsAdapter::class, $adapter);
    }

    public function test_constructor_with_security_token(): void
    {
        $adapter = new HuaweiObsAdapter(
            $this->accessKeyId,
            $this->secretAccessKey,
            $this->bucket,
            $this->endpoint,
            null,
            null,
            'test-token',
            3,
            1,
            false,
            false,
            true
        );

        $this->assertInstanceOf(HuaweiObsAdapter::class, $adapter);
    }

    public function test_constructor_with_prefix(): void
    {
        $adapter = new HuaweiObsAdapter(
            $this->accessKeyId,
            $this->secretAccessKey,
            $this->bucket,
            $this->endpoint,
            'test-prefix',
            null,
            null,
            3,
            1,
            false,
            false,
            true
        );

        $this->assertInstanceOf(HuaweiObsAdapter::class, $adapter);
    }

    public function test_write_with_visibility_and_mime_type(): void
    {
        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andReturn(['HttpStatusCode' => 200]);

        $this->mockClient->shouldReceive('putObject')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
                'Body' => 'test content',
                'ACL' => 'public-read',
                'ContentType' => 'text/plain',
            ])
            ->once()
            ->andReturn(['HttpStatusCode' => 200]);

        $config = new Config([
            'visibility' => Visibility::PUBLIC,
            'mimetype' => 'text/plain',
        ]);

        $this->adapter->write('test-file.txt', 'test content', $config);
        $this->assertTrue(true); // Assert that no exception was thrown
    }
}
