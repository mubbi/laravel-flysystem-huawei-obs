<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests\Adapter;

use LaravelFlysystemHuaweiObs\HuaweiObsAdapter;
use League\Flysystem\Config;
use League\Flysystem\Visibility;
use Mockery as m;
use Obs\ObsClient;
use Obs\ObsException;
use PHPUnit\Framework\TestCase;

class HuaweiObsAdapterTest extends TestCase
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

        // Mark authentication as valid to bypass actual headBucket call
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

    public function test_file_exists_returns_true_on200(): void
    {
        $client = m::mock(ObsClient::class);
        $client->shouldReceive('getObjectMetadata')
            ->once()
            ->with(['Bucket' => 'my-bucket', 'Key' => 'foo.txt'])
            ->andReturn(['HttpStatusCode' => 200]);

        $adapter = $this->makeAdapterWithMockedClient($client);

        self::assertTrue($adapter->fileExists('foo.txt'));
    }

    public function test_file_exists_returns_false_on_not_found(): void
    {
        $client = m::mock(ObsClient::class);
        $exception = m::mock(ObsException::class);
        $exception->shouldReceive('getExceptionCode')->andReturn('NoSuchKey');
        $exception->shouldReceive('getMessage')->andReturn('NoSuchKey');

        $client->shouldReceive('getObjectMetadata')
            ->once()
            ->andThrow($exception);

        $adapter = $this->makeAdapterWithMockedClient($client);

        self::assertFalse($adapter->fileExists('foo.txt'));
    }

    public function test_write_sets_acl_from_visibility(): void
    {
        $client = m::mock(ObsClient::class);
        $client->shouldReceive('putObject')
            ->once()
            ->with(m::on(function ($arg) {
                return $arg['Bucket'] === 'my-bucket'
                    && $arg['Key'] === 'foo.txt'
                    && $arg['Body'] === 'content'
                    && $arg['ACL'] === 'public-read';
            }))
            ->andReturn(['HttpStatusCode' => 200]);

        $adapter = $this->makeAdapterWithMockedClient($client);

        $adapter->write('foo.txt', 'content', new Config(['visibility' => Visibility::PUBLIC]));
        self::assertTrue(true); // no exception
    }

    public function test_read_returns_string_content(): void
    {
        $client = m::mock(ObsClient::class);
        $client->shouldReceive('getObject')
            ->once()
            ->with(['Bucket' => 'my-bucket', 'Key' => 'foo.txt'])
            ->andReturn(['Body' => 'abc']);

        $adapter = $this->makeAdapterWithMockedClient($client);

        self::assertSame('abc', $adapter->read('foo.txt'));
    }

    public function test_create_signed_url_returns_value(): void
    {
        $client = m::mock(ObsClient::class);
        $client->shouldReceive('createSignedUrl')
            ->once()
            ->andReturn(['SignedUrl' => 'https://signed.example.com/u']);

        $adapter = $this->makeAdapterWithMockedClient($client);

        $url = $adapter->createSignedUrl('foo.txt', 'GET', 60, []);
        self::assertSame('https://signed.example.com/u', $url);
    }

    public function test_url_for_public_object_builds_public_endpoint_url(): void
    {
        $client = m::mock(ObsClient::class);
        $client->shouldReceive('getObjectAcl')->once()->andReturn([
            'Grants' => [[
                'Grantee' => ['URI' => 'http://acs.amazonaws.com/groups/global/AllUsers'],
                'Permission' => 'READ',
            ]],
        ]);
        // Adapter now uses its own configured endpoint; no need to query client config

        $adapter = $this->makeAdapterWithMockedClient($client);

        $url = $adapter->url('foo.txt');
        self::assertSame('https://obs.example.com/my-bucket/foo.txt', $url);
    }

    public function test_url_for_private_object_falls_back_to_signed_url(): void
    {
        $client = m::mock(ObsClient::class);
        $client->shouldReceive('getObjectAcl')->once()->andReturn(['Grants' => []]);
        $client->shouldReceive('createSignedUrl')->once()->andReturn(['SignedUrl' => 'https://signed.example.com/u']);

        $adapter = $this->makeAdapterWithMockedClient($client);

        $url = $adapter->url('foo.txt');
        self::assertSame('https://signed.example.com/u', $url);
    }
}
