<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests\Adapter;

use LaravelFlysystemHuaweiObs\HuaweiObsAdapter;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\Visibility;
use Mockery as m;
use Obs\ObsClient;
use Obs\ObsException;
use PHPUnit\Framework\TestCase;

class HuaweiObsAdapterFullTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    private function makeAdapterWithMockedClient(?ObsClient $client = null): HuaweiObsAdapter
    {
        $adapter = new HuaweiObsAdapter(
            'key',
            'secret',
            'my-bucket',
            'https://obs.example.com',
            null,
            null,
            null,
            1,
            0,
            false,
            false,
            true,
            true
        );

        $ref = new \ReflectionClass($adapter);

        $authProp = $ref->getProperty('authenticated');
        $authProp->setAccessible(true);
        $authProp->setValue($adapter, true);

        $authExpiryProp = $ref->getProperty('authCacheExpiry');
        $authExpiryProp->setAccessible(true);
        $authExpiryProp->setValue($adapter, time() + 3600);

        if ($client === null) {
            $client = m::mock(ObsClient::class);
        }

        $clientProp = $ref->getProperty('client');
        $clientProp->setAccessible(true);
        $clientProp->setValue($adapter, $client);

        return $adapter;
    }

    public function test_directory_exists_true(): void
    {
        $client = m::mock(ObsClient::class);
        $client->shouldReceive('listObjects')->once()->with(m::subset([
            'Bucket' => 'my-bucket',
            'Prefix' => 'dir/',
        ]))->andReturn([
            'Contents' => [['Key' => 'dir/file.txt']],
        ]);

        $adapter = $this->makeAdapterWithMockedClient($client);
        self::assertTrue($adapter->directoryExists('dir'));
    }

    public function test_directory_exists_false_on_not_found(): void
    {
        $client = m::mock(ObsClient::class);
        $exception = m::mock(ObsException::class);
        $exception->shouldReceive('getExceptionCode')->andReturn('NoSuchKey');
        $exception->shouldReceive('getMessage')->andReturn('NoSuchKey');

        $client->shouldReceive('listObjects')->once()->andThrow($exception);

        $adapter = $this->makeAdapterWithMockedClient($client);
        self::assertFalse($adapter->directoryExists('missing'));
    }

    public function test_write_stream_and_read_stream(): void
    {
        $stream = fopen('php://temp', 'r+');
        $this->assertIsResource($stream);
        if (! is_resource($stream)) {
            $this->fail('Unable to create temp stream');
        }
        fwrite($stream, 'hello');
        rewind($stream);

        $client = m::mock(ObsClient::class);
        $client->shouldReceive('putObject')->once()->with(m::on(function ($arg) {
            return $arg['Bucket'] === 'my-bucket' && $arg['Key'] === 'foo.txt' && $arg['ACL'] === 'private';
        }))->andReturn(['HttpStatusCode' => 200]);

        $client->shouldReceive('getObject')->once()->with(['Bucket' => 'my-bucket', 'Key' => 'foo.txt'])->andReturn([
            'Body' => 'hello',
        ]);

        $adapter = $this->makeAdapterWithMockedClient($client);
        $adapter->writeStream('foo.txt', $stream, new Config);

        $out = $adapter->readStream('foo.txt');
        self::assertIsResource($out);
        self::assertSame('hello', stream_get_contents($out));
        fclose($out);
    }

    public function test_read_not_found_throws(): void
    {
        $client = m::mock(ObsClient::class);
        $ex = m::mock(ObsException::class);
        $ex->shouldReceive('getExceptionCode')->andReturn('NoSuchKey');
        $ex->shouldReceive('getMessage')->andReturn('NoSuchKey');
        $client->shouldReceive('getObject')->once()->andThrow($ex);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $this->expectException(\League\Flysystem\UnableToReadFile::class);
        $adapter->read('missing.txt');
    }

    public function test_read_stream_not_found_throws(): void
    {
        $client = m::mock(ObsClient::class);
        $ex = m::mock(ObsException::class);
        $ex->shouldReceive('getExceptionCode')->andReturn('NoSuchKey');
        $ex->shouldReceive('getMessage')->andReturn('NoSuchKey');
        $client->shouldReceive('getObject')->once()->andThrow($ex);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $this->expectException(\League\Flysystem\UnableToReadFile::class);
        $adapter->readStream('missing.txt');
    }

    public function test_delete_and_create_directory(): void
    {
        $client = m::mock(ObsClient::class);
        $client->shouldReceive('deleteObject')->once()->with(['Bucket' => 'my-bucket', 'Key' => 'a.txt']);

        $client->shouldReceive('putObject')->once()->with(m::on(function ($arg) {
            return $arg['Bucket'] === 'my-bucket' && $arg['Key'] === 'dir/' && $arg['Body'] === '';
        }));

        $adapter = $this->makeAdapterWithMockedClient($client);
        $adapter->delete('a.txt');
        $adapter->createDirectory('dir', new Config);
        self::assertTrue(true);
    }

    public function test_set_visibility_and_visibility_read(): void
    {
        $client = m::mock(ObsClient::class);
        $client->shouldReceive('setObjectAcl')->once()->with(m::on(function ($arg) {
            return $arg['ACL'] === 'public-read';
        }));
        $client->shouldReceive('getObjectAcl')->once()->andReturn(['Grants' => [[
            'Grantee' => ['URI' => 'http://acs.amazonaws.com/groups/global/AllUsers'],
            'Permission' => 'READ',
        ]]]);

        $adapter = $this->makeAdapterWithMockedClient($client);
        $adapter->setVisibility('x.txt', Visibility::PUBLIC);
        $attributes = $adapter->visibility('x.txt');
        self::assertInstanceOf(FileAttributes::class, $attributes);
        self::assertSame('public', $attributes->visibility());
    }

    public function test_metadata_readers(): void
    {
        $client = m::mock(ObsClient::class);
        $client->shouldReceive('getObjectMetadata')->times(4)->andReturn(
            ['ContentType' => 'text/plain'],
            ['LastModified' => '2020-01-01 00:00:00'],
            ['ContentLength' => 123],
            ['HttpStatusCode' => 200]
        );

        $adapter = $this->makeAdapterWithMockedClient($client);
        self::assertSame('text/plain', $adapter->mimeType('m.txt')->mimeType());
        self::assertIsInt($adapter->lastModified('m.txt')->lastModified());
        self::assertSame(123, $adapter->fileSize('m.txt')->fileSize());
        self::assertTrue($adapter->fileExists('m.txt'));
    }

    public function test_metadata_not_found_throws(): void
    {
        $client = m::mock(ObsClient::class);
        $ex = m::mock(ObsException::class);
        $ex->shouldReceive('getExceptionCode')->andReturn('NoSuchKey');
        $ex->shouldReceive('getMessage')->andReturn('NoSuchKey');
        // Visibility uses ACL; ensure ACL exception is translated to UnableToRetrieveMetadata
        $client->shouldReceive('getObjectAcl')->andThrow($ex);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $this->expectException(\League\Flysystem\UnableToRetrieveMetadata::class);
        $adapter->visibility('x');
        // The following will not run if previous throws; split into separate try/catch
    }

    public function test_mime_type_not_found_throws(): void
    {
        $client = m::mock(ObsClient::class);
        $ex = m::mock(ObsException::class);
        $ex->shouldReceive('getExceptionCode')->andReturn('NoSuchKey');
        $ex->shouldReceive('getMessage')->andReturn('NoSuchKey');
        $client->shouldReceive('getObjectMetadata')->once()->andThrow($ex);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $this->expectException(\League\Flysystem\UnableToRetrieveMetadata::class);
        $adapter->mimeType('x');
    }

    public function test_last_modified_not_found_throws(): void
    {
        $client = m::mock(ObsClient::class);
        $ex = m::mock(ObsException::class);
        $ex->shouldReceive('getExceptionCode')->andReturn('NoSuchKey');
        $ex->shouldReceive('getMessage')->andReturn('NoSuchKey');
        $client->shouldReceive('getObjectMetadata')->once()->andThrow($ex);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $this->expectException(\League\Flysystem\UnableToRetrieveMetadata::class);
        $adapter->lastModified('x');
    }

    public function test_file_size_not_found_throws(): void
    {
        $client = m::mock(ObsClient::class);
        $ex = m::mock(ObsException::class);
        $ex->shouldReceive('getExceptionCode')->andReturn('NoSuchKey');
        $ex->shouldReceive('getMessage')->andReturn('NoSuchKey');
        $client->shouldReceive('getObjectMetadata')->once()->andThrow($ex);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $this->expectException(\League\Flysystem\UnableToRetrieveMetadata::class);
        $adapter->fileSize('x');
    }

    public function test_list_contents_shallow_and_deep(): void
    {
        $client = m::mock(ObsClient::class);

        // First call: shallow with one file and one dir, set NextMarker to end
        $client->shouldReceive('listObjects')->once()->andReturn([
            'Contents' => [
                ['Key' => 'root/file1.txt', 'Size' => 10, 'LastModified' => '2020-01-01 00:00:00'],
                ['Key' => 'root/'], // directory marker should be skipped
            ],
            'CommonPrefixes' => [
                ['Prefix' => 'root/dir1/'],
            ],
            'NextMarker' => null,
        ]);

        // Deep call with pagination across two pages
        $client->shouldReceive('listObjects')->once()->andReturn([
            'Contents' => [
                ['Key' => 'root-deep/file2.txt', 'Size' => 5, 'LastModified' => '2020-01-01 00:00:00'],
            ],
            'CommonPrefixes' => [
                ['Prefix' => 'root-deep/sub/'],
            ],
            'NextMarker' => 'token-1',
        ]);

        $client->shouldReceive('listObjects')->once()->andReturn([
            'Contents' => [
                ['Key' => 'root-deep/file3.txt', 'Size' => 7, 'LastModified' => '2020-01-01 00:00:00'],
            ],
            'CommonPrefixes' => [],
            'NextMarker' => null,
        ]);

        $adapter = $this->makeAdapterWithMockedClient($client);

        $shallow = iterator_to_array($adapter->listContents('root', false));
        self::assertGreaterThanOrEqual(2, count($shallow));
        self::assertInstanceOf(FileAttributes::class, $shallow[0]);
        self::assertInstanceOf(DirectoryAttributes::class, $shallow[1]);

        $deep = iterator_to_array($adapter->listContents('root-deep', true));
        self::assertGreaterThanOrEqual(2, count($deep));
    }

    public function test_move_and_copy(): void
    {
        $client = m::mock(ObsClient::class);
        $client->shouldReceive('copyObject')->once();
        $client->shouldReceive('deleteObject')->once();

        $client2 = m::mock(ObsClient::class);
        $client2->shouldReceive('copyObject')->once();

        $adapter = $this->makeAdapterWithMockedClient($client);
        $adapter->move('a.txt', 'b.txt', new Config);

        $adapter2 = $this->makeAdapterWithMockedClient($client2);
        $adapter2->copy('c.txt', 'd.txt', new Config);

        self::assertTrue(true);
    }

    public function test_move_throws_on_error(): void
    {
        $client = m::mock(ObsClient::class);
        $ex = m::mock(ObsException::class);
        $client->shouldReceive('copyObject')->once()->andThrow($ex);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $this->expectException(\League\Flysystem\UnableToMoveFile::class);
        $adapter->move('a', 'b', new Config);
    }

    public function test_copy_throws_on_error(): void
    {
        $client = m::mock(ObsClient::class);
        $ex = m::mock(ObsException::class);
        $client->shouldReceive('copyObject')->once()->andThrow($ex);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $this->expectException(\League\Flysystem\UnableToCopyFile::class);
        $adapter->copy('a', 'b', new Config);
    }

    public function test_file_exists_throws_on_obs_error(): void
    {
        $client = m::mock(ObsClient::class);
        $ex = m::mock(ObsException::class);
        $ex->shouldReceive('getExceptionCode')->andReturn(null);
        $ex->shouldReceive('getMessage')->andReturn('boom');
        $client->shouldReceive('getObjectMetadata')->once()->andThrow($ex);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $this->expectException(\League\Flysystem\UnableToCheckFileExistence::class);
        $adapter->fileExists('x');
    }

    public function test_directory_exists_throws_on_obs_error(): void
    {
        $client = m::mock(ObsClient::class);
        $ex = m::mock(ObsException::class);
        $ex->shouldReceive('getExceptionCode')->andReturn(null);
        $ex->shouldReceive('getMessage')->andReturn('boom');
        $client->shouldReceive('listObjects')->once()->andThrow($ex);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $this->expectException(\League\Flysystem\UnableToCheckDirectoryExistence::class);
        $adapter->directoryExists('x');
    }

    public function test_write_throws_on_obs_error(): void
    {
        $client = m::mock(ObsClient::class);
        $ex = m::mock(ObsException::class);
        $ex->shouldReceive('getMessage')->andReturn('err');
        $client->shouldReceive('putObject')->once()->andThrow($ex);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $this->expectException(\League\Flysystem\UnableToWriteFile::class);
        $adapter->write('a', 'b', new Config);
    }

    public function test_write_stream_throws_on_obs_error(): void
    {
        $client = m::mock(ObsClient::class);
        $ex = m::mock(ObsException::class);
        $ex->shouldReceive('getMessage')->andReturn('err');
        $client->shouldReceive('putObject')->once()->andThrow($ex);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $this->expectException(\League\Flysystem\UnableToWriteFile::class);
        $stream = fopen('php://temp', 'r+');
        $this->assertIsResource($stream);
        if (is_resource($stream)) {
            $adapter->writeStream('a', $stream, new Config);
            fclose($stream);
        }
    }

    public function test_delete_throws_on_obs_error(): void
    {
        $client = m::mock(ObsClient::class);
        $ex = m::mock(ObsException::class);
        $ex->shouldReceive('getMessage')->andReturn('err');
        $client->shouldReceive('deleteObject')->once()->andThrow($ex);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $this->expectException(\League\Flysystem\UnableToDeleteFile::class);
        $adapter->delete('a');
    }

    public function test_create_directory_throws_on_obs_error(): void
    {
        $client = m::mock(ObsClient::class);
        $ex = m::mock(ObsException::class);
        $ex->shouldReceive('getMessage')->andReturn('err');
        $client->shouldReceive('putObject')->once()->andThrow($ex);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $this->expectException(\League\Flysystem\UnableToCreateDirectory::class);
        $adapter->createDirectory('dir', new Config);
    }

    public function test_url_not_found_throws_runtime(): void
    {
        $client = m::mock(ObsClient::class);
        $ex = m::mock(ObsException::class);
        $ex->shouldReceive('getExceptionCode')->andReturn('NoSuchKey');
        $ex->shouldReceive('getMessage')->andReturn('NoSuchKey');
        $client->shouldReceive('getObjectAcl')->once()->andThrow($ex);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $this->expectException(\RuntimeException::class);
        $adapter->url('missing');
    }

    public function test_get_temporary_url_propagates_unable_to_create_signed_url(): void
    {
        $client = m::mock(ObsClient::class);
        $ex = m::mock(ObsException::class);
        $ex->shouldReceive('getExceptionCode')->andReturn('NoSuchKey');
        $ex->shouldReceive('getMessage')->andReturn('NoSuchKey');
        // When creating the signed URL from the client, throw ObsException which is
        // then wrapped by the adapter into UnableToCreateSignedUrl
        $client->shouldReceive('createSignedUrl')->once()->andThrow($ex);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $this->expectException(\LaravelFlysystemHuaweiObs\Exceptions\UnableToCreateSignedUrl::class);
        $adapter->getTemporaryUrl('missing', new \DateTimeImmutable('+1 hour'));
    }

    public function test_list_contents_obs_exception_throws_runtime(): void
    {
        $client = m::mock(ObsClient::class);
        $ex = m::mock(ObsException::class);
        $ex->shouldReceive('getMessage')->andReturn('err');
        $client->shouldReceive('listObjects')->once()->andThrow($ex);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $this->expectException(\RuntimeException::class);
        iterator_to_array($adapter->listContents('x', true));
    }

    public function test_temporary_urls(): void
    {
        $client = m::mock(ObsClient::class);
        $client->shouldReceive('createSignedUrl')->andReturn(['SignedUrl' => 'https://signed.example.com/tmp']);
        $client->shouldReceive('getObjectAcl')->andReturn(['Grants' => []]);

        $adapter = $this->makeAdapterWithMockedClient($client);
        $expires = new \DateTimeImmutable('+1 hour');
        $tmp = $adapter->getTemporaryUrl('x.txt', $expires, ['method' => 'GET']);
        self::assertSame('https://signed.example.com/tmp', $tmp);

        $alias = $adapter->getUrl('x.txt');
        self::assertIsString($alias);

        $upload = $adapter->temporaryUploadUrl('x.txt', $expires);
        self::assertIsString($upload);
    }

    public function test_get_temporary_url_past_clamped(): void
    {
        $client = m::mock(ObsClient::class);
        $client->shouldReceive('createSignedUrl')->andReturn(['SignedUrl' => 'https://signed.example.com/tmp']);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $past = new \DateTimeImmutable('-1 hour');
        $tmp = $adapter->getTemporaryUrl('x', $past);
        self::assertIsString($tmp);
    }

    public function test_url_obs_error_throws_runtime(): void
    {
        $client = m::mock(ObsClient::class);
        $ex = m::mock(ObsException::class);
        $ex->shouldReceive('getExceptionCode')->andReturn(null);
        $ex->shouldReceive('getMessage')->andReturn('some error');
        $client->shouldReceive('getObjectAcl')->andThrow($ex);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $this->expectException(\RuntimeException::class);
        $adapter->url('x');
    }

    public function test_all_files_and_directories_helpers(): void
    {
        $client = m::mock(ObsClient::class);
        // These helpers will invoke listObjects multiple times. Prepare sequential returns.
        $client->shouldReceive('listObjects')->times(4)->andReturn(
            [
                'Contents' => [
                    ['Key' => 'a/file.txt', 'Size' => 1, 'LastModified' => '2020-01-01 00:00:00'],
                ],
                'CommonPrefixes' => [
                    ['Prefix' => 'a/sub/'],
                ],
                'NextMarker' => null,
            ],
            [
                'Contents' => [],
                'CommonPrefixes' => [
                    ['Prefix' => 'b/'],
                ],
                'NextMarker' => null,
            ],
            [
                'Contents' => [
                    ['Key' => 'a/file2.txt', 'Size' => 2, 'LastModified' => '2020-01-01 00:00:00'],
                ],
                'CommonPrefixes' => [],
                'NextMarker' => null,
            ],
            [
                'Contents' => [],
                'CommonPrefixes' => [
                    ['Prefix' => 'b/sub2/'],
                ],
                'NextMarker' => null,
            ]
        );

        $adapter = $this->makeAdapterWithMockedClient($client);
        self::assertNotEmpty($adapter->allFiles());
        self::assertNotEmpty($adapter->allDirectories());
        self::assertNotEmpty($adapter->files('a'));
        self::assertNotEmpty($adapter->directories('b'));
    }

    public function test_optimized_listing_and_stats(): void
    {
        $client = m::mock(ObsClient::class);
        // Three sequential calls for files, directories, and stats
        $client->shouldReceive('listObjects')->times(3)->andReturn(
            [
                'Contents' => [
                    ['Key' => 'opt/file1.txt', 'Size' => 10, 'LastModified' => '2020-01-01 00:00:00'],
                ],
                'CommonPrefixes' => [
                    ['Prefix' => 'opt/dir1/'],
                ],
                'NextMarker' => null,
            ],
            [
                'Contents' => [],
                'CommonPrefixes' => [
                    ['Prefix' => 'opt/dir2/'],
                ],
                'NextMarker' => null,
            ],
            [
                'Contents' => [
                    ['Key' => 'opt/file2.txt', 'Size' => 20, 'LastModified' => '2020-01-02 00:00:00'],
                ],
                'CommonPrefixes' => [],
                'NextMarker' => null,
            ]
        );

        $adapter = $this->makeAdapterWithMockedClient($client);

        $files = $adapter->allFilesOptimized(10, 5);
        $dirs = $adapter->allDirectoriesOptimized(10, 5);
        $stats = $adapter->getStorageStats(10, 5);

        self::assertNotEmpty($files);
        self::assertNotEmpty($dirs);
        self::assertIsArray($stats);
        self::assertArrayHasKey('total_files', $stats);
        self::assertArrayHasKey('total_directories', $stats);
    }

    public function test_list_contents_break_on_same_marker(): void
    {
        $client = m::mock(ObsClient::class);
        $client->shouldReceive('listObjects')->once()->andReturn([
            'Contents' => [],
            'CommonPrefixes' => [],
            'NextMarker' => 'A',
        ]);
        $client->shouldReceive('listObjects')->once()->andReturn([
            'Contents' => [],
            'CommonPrefixes' => [],
            'NextMarker' => 'A',
        ]);
        $adapter = $this->makeAdapterWithMockedClient($client);
        $result = iterator_to_array($adapter->listContents('x', true));
        self::assertIsArray($result);
    }

    public function test_delete_directory_with_pagination(): void
    {
        $client = m::mock(ObsClient::class);
        // First page returns one object and a next marker
        $client->shouldReceive('listObjects')->once()->andReturn([
            'Contents' => [
                ['Key' => 'dir/x.txt'],
            ],
            'NextMarker' => 'mk-1',
        ]);
        // Second page returns another object and ends
        $client->shouldReceive('listObjects')->once()->andReturn([
            'Contents' => [
                ['Key' => 'dir/y.txt'],
            ],
            'NextMarker' => null,
        ]);
        $client->shouldReceive('deleteObjects')->once()->with(m::on(function ($arg) {
            return $arg['Bucket'] === 'my-bucket' && count($arg['Objects']) === 2;
        }));

        $adapter = $this->makeAdapterWithMockedClient($client);
        $adapter->deleteDirectory('dir');
        self::assertTrue(true);
    }
}
