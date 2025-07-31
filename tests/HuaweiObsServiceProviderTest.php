<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests;

use Illuminate\Support\Facades\Storage;
use LaravelFlysystemHuaweiObs\HuaweiObsServiceProvider;
use Orchestra\Testbench\TestCase;

class HuaweiObsServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            HuaweiObsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('filesystems.disks.huawei-obs', [
            'driver' => 'huawei-obs',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'bucket' => 'test-bucket',
            'endpoint' => 'https://obs.cn-north-1.myhuaweicloud.com',
            'region' => 'cn-north-1',
            'prefix' => null,
            'visibility' => 'public',
            'throw' => false,
        ]);
    }

    public function test_huawei_obs_disk_is_registered(): void
    {
        $disk = Storage::disk('huawei-obs');
        $this->assertInstanceOf(\Illuminate\Filesystem\FilesystemAdapter::class, $disk);
    }

    public function test_huawei_obs_disk_with_prefix(): void
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;
        $app['config']->set('filesystems.disks.huawei-obs.prefix', 'test-prefix');

        $disk = Storage::disk('huawei-obs');
        $this->assertInstanceOf(\Illuminate\Filesystem\FilesystemAdapter::class, $disk);
    }

    public function test_huawei_obs_disk_with_http_client_config(): void
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;
        $app['config']->set('filesystems.disks.huawei-obs.http_client', [
            'timeout' => 60,
            'connect_timeout' => 20,
            'verify' => false,
            'proxy' => 'http://proxy.example.com:8080',
            'headers' => ['User-Agent' => 'Custom Agent'],
        ]);

        $disk = Storage::disk('huawei-obs');
        $this->assertInstanceOf(\Illuminate\Filesystem\FilesystemAdapter::class, $disk);
    }
}
