<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests\ServiceProvider;

use Illuminate\Support\Facades\Storage;
use LaravelFlysystemHuaweiObs\HuaweiObsServiceProvider;
use Orchestra\Testbench\TestCase;

class HuaweiObsServiceProviderValidationTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [HuaweiObsServiceProvider::class];
    }

    public function test_missing_config_throws(): void
    {
        /** @var \Illuminate\Contracts\Foundation\Application $app */
        $app = $this->app;
        $app->make('config')->set('filesystems.disks.bad', [
            'driver' => 'huawei-obs',
            // missing key/secret/bucket/endpoint
        ]);

        $this->expectException(\InvalidArgumentException::class);
        Storage::disk('bad');
    }

    public function test_invalid_endpoint_throws(): void
    {
        /** @var \Illuminate\Contracts\Foundation\Application $app */
        $app = $this->app;
        $app->make('config')->set('filesystems.disks.bad2', [
            'driver' => 'huawei-obs',
            'key' => 'k',
            'secret' => 's',
            'bucket' => 'b',
            'endpoint' => 'not-a-url',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        Storage::disk('bad2');
    }
}
