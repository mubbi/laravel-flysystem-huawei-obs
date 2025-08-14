<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests\Laravel;

use LaravelFlysystemHuaweiObs\LaravelHuaweiObsAdapter;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use Mockery as m;
use Obs\ObsClient;
use Orchestra\Testbench\TestCase;

class LaravelHuaweiObsAdapterMoreTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [\LaravelFlysystemHuaweiObs\HuaweiObsServiceProvider::class];
    }

    /**
     * @return array{0: LaravelHuaweiObsAdapter, 1: ObsClient|m\MockInterface}
     */
    private function makeAdapter(): array
    {
        $adapter = new LaravelHuaweiObsAdapter(
            'key',
            'secret',
            'bucket',
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

        $refLaravel = new \ReflectionClass($adapter);
        $prop = $refLaravel->getProperty('adapter');
        $prop->setAccessible(true);
        $base = $prop->getValue($adapter);

        $refBase = new \ReflectionClass($base);
        $authProp = $refBase->getProperty('authenticated');
        $authProp->setAccessible(true);
        $authProp->setValue($base, true);
        $authExpiryProp = $refBase->getProperty('authCacheExpiry');
        $authExpiryProp->setAccessible(true);
        $authExpiryProp->setValue($base, time() + 3600);

        /** @var ObsClient|m\MockInterface $client */
        $client = m::mock(ObsClient::class);
        $clientProp = $refBase->getProperty('client');
        $clientProp->setAccessible(true);
        $clientProp->setValue($base, $client);

        return [$adapter, $client];
    }

    public function test_files_and_directories_helpers(): void
    {
        [$adapter, $client] = $this->makeAdapter();

        $client->shouldReceive('listObjects')->twice()->andReturn([
            'Contents' => [
                ['Key' => 'a.txt', 'Size' => 3, 'LastModified' => gmdate('c')],
            ],
            'CommonPrefixes' => [
                ['Prefix' => 'dir/'],
            ],
        ]);

        $files = $adapter->files('');
        $dirs = $adapter->directories('');

        $this->assertSame(['a.txt'], $files);
        $this->assertSame(['dir'], $dirs);
    }

    public function test_getters_map_to_attributes(): void
    {
        [$adapter, $client] = $this->makeAdapter();

        $client->shouldReceive('getObjectMetadata')->andReturn([
            'ContentType' => 'text/plain',
            'ContentLength' => 10,
            'LastModified' => '2020-01-01T00:00:00Z',
        ])->byDefault();

        $client->shouldReceive('getObjectAcl')->andReturn(['Grants' => []]);

        $this->assertSame(10, $adapter->size('foo.txt'));
        $this->assertSame('text/plain', $adapter->getMimeType('foo.txt'));
        $this->assertIsInt($adapter->getLastModified('foo.txt'));

        $attributes = $adapter->visibility('foo.txt');
        $this->assertInstanceOf(FileAttributes::class, $attributes);
        $this->assertSame('private', $adapter->getVisibility('foo.txt'));
    }

    public function test_list_contents_optimized_yields_items(): void
    {
        [$adapter, $client] = $this->makeAdapter();

        $client->shouldReceive('listObjects')->once()->andReturn([
            'Contents' => [
                ['Key' => 'a.txt', 'Size' => 1, 'LastModified' => gmdate('c')],
            ],
            'CommonPrefixes' => [
                ['Prefix' => 'dir/'],
            ],
            'NextMarker' => null,
        ]);

        $items = iterator_to_array($adapter->listContentsOptimized('', true, 10, 1));
        $this->assertNotEmpty($items);
        $this->assertInstanceOf(FileAttributes::class, $items[0]);
        $this->assertInstanceOf(DirectoryAttributes::class, $items[1]);
    }

    public function test_passthrough_temporary_url_and_url(): void
    {
        [$adapter, $client] = $this->makeAdapter();

        $client->shouldReceive('getObjectAcl')->andReturn(['Grants' => []]);
        $client->shouldReceive('createSignedUrl')->andReturn(['SignedUrl' => 'https://signed.example.com/x']);

        $tmp = $adapter->getTemporaryUrl('x', new \DateTimeImmutable('+1 hour'));
        $this->assertSame('https://signed.example.com/x', $tmp);

        $url = $adapter->url('x');
        $this->assertSame('https://signed.example.com/x', $url);
    }
}
