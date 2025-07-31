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

    public function test_logging_disabled_by_default(): void
    {
        // Create adapter with logging disabled
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
            false, // loggingEnabled
            false, // logOperations
            false  // logErrors
        );

        // Replace with mock client
        $reflection = new \ReflectionClass($adapter);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($adapter, $this->mockClient);

        // Mock successful operation
        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andReturn(['HttpStatusCode' => 200]);

        $this->mockClient->shouldReceive('getObjectMetadata')
            ->with([
                'Bucket' => $this->bucket,
                'Key' => 'test-file.txt',
            ])
            ->andReturn([
                'HttpStatusCode' => 200,
                'ContentLength' => 100,
                'LastModified' => '2023-01-01T00:00:00Z',
            ]);

        // Should not throw any logging-related errors
        $result = $adapter->fileExists('test-file.txt');
        $this->assertTrue($result);
    }
}
