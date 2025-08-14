<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests\ServiceProvider;

use Illuminate\Filesystem\FilesystemAdapter as IlluminateFilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use LaravelFlysystemHuaweiObs\LaravelHuaweiObsAdapter;
use Orchestra\Testbench\TestCase;

class HuaweiObsServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [\LaravelFlysystemHuaweiObs\HuaweiObsServiceProvider::class];
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

    public function test_disk_is_registered(): void
    {
        $disk = Storage::disk('obs');
        // Ensure adapter is our custom adapter
        $this->assertInstanceOf(IlluminateFilesystemAdapter::class, $disk);
        $this->assertInstanceOf(LaravelHuaweiObsAdapter::class, $disk->getAdapter());

        // Macroable methods are resolved via __call, so use hasMacro()
        $this->assertTrue(IlluminateFilesystemAdapter::hasMacro('temporaryUploadUrl'));
        $this->assertTrue(IlluminateFilesystemAdapter::hasMacro('createSignedUrl'));
    }
}
