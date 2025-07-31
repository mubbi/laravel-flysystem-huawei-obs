<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests\Console;

use LaravelFlysystemHuaweiObs\Console\TestHuaweiObsCommand;
use LaravelFlysystemHuaweiObs\HuaweiObsServiceProvider;
use Orchestra\Testbench\TestCase;

class TestHuaweiObsCommandTest extends TestCase
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

    public function test_command_instantiation(): void
    {
        $command = new TestHuaweiObsCommand;

        $this->assertInstanceOf(TestHuaweiObsCommand::class, $command);
    }

    public function test_command_has_description(): void
    {
        $command = new TestHuaweiObsCommand;

        $this->assertEquals(
            'Test Huawei OBS connectivity and basic operations',
            $command->getDescription()
        );
    }
}
