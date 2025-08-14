<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests\Console;

use Illuminate\Support\Facades\Storage;
use LaravelFlysystemHuaweiObs\HuaweiObsAdapter;
use LaravelFlysystemHuaweiObs\HuaweiObsServiceProvider;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class TestHuaweiObsCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [HuaweiObsServiceProvider::class];
    }

    private function makeFakeDisk(object $adapter): object
    {
        return new class($adapter)
        {
            /** @var array<string,string> */
            private array $files = [];

            public function __construct(private object $adapter) {}

            public function getDriver(): object
            {
                return new class($this->adapter)
                {
                    public function __construct(private object $adapter) {}

                    public function getAdapter(): object
                    {
                        return $this->adapter;
                    }
                };
            }

            public function put(string $path, string $contents): bool
            {
                $this->files[$path] = $contents;

                return true;
            }

            public function get(string $path): string
            {
                return $this->files[$path] ?? '';
            }

            public function exists(string $path): bool
            {
                return array_key_exists($path, $this->files);
            }

            public function size(string $path): int
            {
                return isset($this->files[$path]) ? strlen($this->files[$path]) : 0;
            }

            public function delete(string $path): bool
            {
                unset($this->files[$path]);

                return true;
            }
        };
    }

    public function test_command_runs_all_tests_successfully(): void
    {
        /** @var HuaweiObsAdapter|m\MockInterface $baseAdapter */
        $baseAdapter = m::mock(HuaweiObsAdapter::class);
        $baseAdapter->shouldReceive('refreshAuthentication')->once();
        $baseAdapter->shouldReceive('createSignedUrl')->atLeast()->once()->andReturn('https://signed.example');
        $baseAdapter->shouldReceive('createPostSignature')->atLeast()->once()->andReturn(['ok' => true]);
        $baseAdapter->shouldReceive('setObjectTags')->atLeast()->once();
        $baseAdapter->shouldReceive('getObjectTags')->atLeast()->once()->andReturn(['a' => 'b']);

        $fakeDisk = $this->makeFakeDisk($baseAdapter);

        Storage::shouldReceive('disk')->once()->with('huawei-obs')->andReturn($fakeDisk);

        $result = $this->artisan('huawei-obs:test', [
            '--disk' => 'huawei-obs',
        ]);
        if (is_int($result)) {
            $this->assertSame(0, $result);
        } else {
            $result->assertExitCode(0);
        }
    }

    public function test_command_returns_error_when_disk_not_huawei_obs(): void
    {
        $notObsAdapter = new \stdClass;
        $fakeDisk = $this->makeFakeDisk($notObsAdapter);

        Storage::shouldReceive('disk')->once()->with('huawei-obs')->andReturn($fakeDisk);

        $result = $this->artisan('huawei-obs:test', [
            '--disk' => 'huawei-obs',
            '--write-test' => true,
        ]);
        if (is_int($result)) {
            $this->assertSame(1, $result);
        } else {
            $result->assertExitCode(1);
        }
    }
}
