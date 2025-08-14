<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests\Laravel;

use Illuminate\Support\Facades\Storage;
use LaravelFlysystemHuaweiObs\HuaweiObsServiceProvider;
use LaravelFlysystemHuaweiObs\LaravelHuaweiObsAdapter;
use LaravelFlysystemHuaweiObs\Tests\Support\HasHuaweiObsMacros;
use League\Flysystem\FilesystemAdapter as LeagueFilesystemAdapter;
use Orchestra\Testbench\TestCase;

class HuaweiObsServiceProviderMacrosTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [HuaweiObsServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('filesystems.disks.obs', [
            'driver' => 'huawei-obs',
            'key' => 'key',
            'secret' => 'secret',
            'bucket' => 'bucket',
            'endpoint' => 'https://obs.example.com',
            'http_client' => [
                'verify' => true,
            ],
        ]);
    }

    public function test_macros_exist_and_adapter_type(): void
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter&HasHuaweiObsMacros $disk */
        $disk = Storage::disk('obs');
        $this->assertTrue(method_exists($disk, '__call'));
        $this->assertTrue(method_exists($disk, 'getAdapter'));
        $this->assertInstanceOf(\Illuminate\Filesystem\FilesystemAdapter::class, $disk);
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $this->assertInstanceOf(LeagueFilesystemAdapter::class, $disk->getAdapter());
        $this->assertInstanceOf(LaravelHuaweiObsAdapter::class, $disk->getAdapter());
        $this->assertTrue($disk::hasMacro('getStorageStats'));
        $this->assertTrue($disk::hasMacro('allFilesOptimized'));
        $this->assertTrue($disk::hasMacro('allDirectoriesOptimized'));
        $this->assertTrue($disk::hasMacro('listContentsOptimized'));
    }

    public function test_macros_delegate_to_adapter_methods(): void
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter&HasHuaweiObsMacros $disk */
        $disk = Storage::disk('obs');

        $this->assertInstanceOf(LaravelHuaweiObsAdapter::class, $disk->getAdapter());

        /** @var LaravelHuaweiObsAdapter $adapter */
        $adapter = $disk->getAdapter();

        // Reach into the underlying base adapter and inject a mocked ObsClient
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

        /** @var \Obs\ObsClient|\Mockery\MockInterface $client */
        $client = \Mockery::mock(\Obs\ObsClient::class);

        // Signed URL and post signature
        $client->shouldReceive('createSignedUrl')->andReturn(['SignedUrl' => 'https://signed.example.com/u'])->byDefault();
        $client->shouldReceive('createPostSignature')->andReturn(['Policy' => 'p', 'Signature' => 's'])->byDefault();

        // Tagging operations
        $client->shouldReceive('setObjectTagging')->andReturnNull()->byDefault();
        $client->shouldReceive('getObjectTagging')->andReturn(['TagSet' => ['k' => 'v']])->byDefault();
        $client->shouldReceive('deleteObjectTagging')->andReturnNull()->byDefault();

        // Restore
        $client->shouldReceive('restoreObject')->andReturnNull()->byDefault();

        // Listing for optimized helpers
        $client->shouldReceive('listObjects')->andReturn([
            'Contents' => [
                ['Key' => 'a.txt', 'Size' => 1, 'LastModified' => gmdate('c')],
            ],
            'CommonPrefixes' => [
                ['Prefix' => 'dir/'],
            ],
            'NextMarker' => null,
        ])->byDefault();

        $clientProp = $refBase->getProperty('client');
        $clientProp->setAccessible(true);
        $clientProp->setValue($base, $client);

        // Exercise macros
        $signedUrl = $disk->createSignedUrl('a.txt', 'GET', 60, []);
        $this->assertIsString($signedUrl);

        $postSig = $disk->createPostSignature('a.txt', [], 60);
        $this->assertIsArray($postSig);

        $disk->setObjectTags('a.txt', ['k' => 'v']);
        $tags = $disk->getObjectTags('a.txt');
        $this->assertSame(['k' => 'v'], $tags);
        $disk->deleteObjectTags('a.txt');

        $disk->restoreObject('a.txt', 1);

        $tmpUpload = $disk->temporaryUploadUrl('a.txt', new \DateTimeImmutable('+1 hour'));
        $this->assertIsString($tmpUpload);

        $stats = $disk->getStorageStats(10, 1);
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_files', $stats);

        $files = $disk->allFilesOptimized(10, 1);
        $dirs = $disk->allDirectoriesOptimized(10, 1);
        $this->assertNotEmpty($files);
        $this->assertNotEmpty($dirs);

        $items = \iterator_to_array($disk->listContentsOptimized('', true, 10, 1));
        $this->assertNotEmpty($items);
    }
}
