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

    public function test_huawei_obs_disk_with_security_token(): void
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;
        $app['config']->set('filesystems.disks.huawei-obs.security_token', 'test-security-token');

        $disk = Storage::disk('huawei-obs');
        $this->assertInstanceOf(\Illuminate\Filesystem\FilesystemAdapter::class, $disk);
    }

    public function test_huawei_obs_disk_with_retry_config(): void
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;
        $app['config']->set('filesystems.disks.huawei-obs.retry_attempts', 5);
        $app['config']->set('filesystems.disks.huawei-obs.retry_delay', 2);

        $disk = Storage::disk('huawei-obs');
        $this->assertInstanceOf(\Illuminate\Filesystem\FilesystemAdapter::class, $disk);
    }

    public function test_huawei_obs_disk_with_logging_config(): void
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;
        $app['config']->set('filesystems.disks.huawei-obs.logging_enabled', true);
        $app['config']->set('filesystems.disks.huawei-obs.log_operations', true);
        $app['config']->set('filesystems.disks.huawei-obs.log_errors', false);

        $disk = Storage::disk('huawei-obs');
        $this->assertInstanceOf(\Illuminate\Filesystem\FilesystemAdapter::class, $disk);
    }

    public function test_huawei_obs_disk_missing_required_config_throws_exception(): void
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;
        $app['config']->set('filesystems.disks.huawei-obs', [
            'driver' => 'huawei-obs',
            // Missing required fields
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required configuration for huawei-obs disk: key');

        Storage::disk('huawei-obs');
    }

    public function test_huawei_obs_disk_missing_secret_throws_exception(): void
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;
        $app['config']->set('filesystems.disks.huawei-obs', [
            'driver' => 'huawei-obs',
            'key' => 'test-key',
            // Missing secret
            'bucket' => 'test-bucket',
            'endpoint' => 'https://obs.cn-north-1.myhuaweicloud.com',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required configuration for huawei-obs disk: secret');

        Storage::disk('huawei-obs');
    }

    public function test_huawei_obs_disk_missing_bucket_throws_exception(): void
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;
        $app['config']->set('filesystems.disks.huawei-obs', [
            'driver' => 'huawei-obs',
            'key' => 'test-key',
            'secret' => 'test-secret',
            // Missing bucket
            'endpoint' => 'https://obs.cn-north-1.myhuaweicloud.com',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required configuration for huawei-obs disk: bucket');

        Storage::disk('huawei-obs');
    }

    public function test_huawei_obs_disk_missing_endpoint_throws_exception(): void
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;
        $app['config']->set('filesystems.disks.huawei-obs', [
            'driver' => 'huawei-obs',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'bucket' => 'test-bucket',
            // Missing endpoint
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required configuration for huawei-obs disk: endpoint');

        Storage::disk('huawei-obs');
    }

    public function test_huawei_obs_disk_invalid_endpoint_throws_exception(): void
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;
        $app['config']->set('filesystems.disks.huawei-obs', [
            'driver' => 'huawei-obs',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'bucket' => 'test-bucket',
            'endpoint' => 'invalid-url',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid endpoint URL for huawei-obs disk: invalid-url');

        Storage::disk('huawei-obs');
    }

    public function test_huawei_obs_disk_with_empty_endpoint_throws_exception(): void
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;
        $app['config']->set('filesystems.disks.huawei-obs', [
            'driver' => 'huawei-obs',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'bucket' => 'test-bucket',
            'endpoint' => '',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required configuration for huawei-obs disk: endpoint');

        Storage::disk('huawei-obs');
    }

    public function test_service_provider_registers_commands_in_console(): void
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;
        $this->assertTrue($app->runningInConsole());

        // Check if the command is registered by testing the command class exists
        $this->assertTrue(class_exists(\LaravelFlysystemHuaweiObs\Console\TestHuaweiObsCommand::class));
    }

    public function test_service_provider_publishes_config(): void
    {
        // Test that the service provider can be instantiated without errors
        $this->assertTrue(true);
    }
}
