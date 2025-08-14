<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests\Laravel;

use LaravelFlysystemHuaweiObs\LaravelHuaweiObsAdapter;
use League\Flysystem\FileAttributes;
use Mockery as m;
use Obs\ObsClient;
use Orchestra\Testbench\TestCase;

class LaravelHuaweiObsAdapterTest extends TestCase
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

    private function makeLaravelAdapterWithMock(): LaravelHuaweiObsAdapter
    {
        $adapter = new LaravelHuaweiObsAdapter(
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

        // Inject mocked underlying ObsClient
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

        $client = m::mock(ObsClient::class);
        $clientProp = $refBase->getProperty('client');
        $clientProp->setAccessible(true);
        $clientProp->setValue($base, $client);

        return $adapter;
    }

    public function test_delegates_read(): void
    {
        $adapter = $this->makeLaravelAdapterWithMock();
        $refLaravel = new \ReflectionClass($adapter);
        $base = $refLaravel->getProperty('adapter');
        $base->setAccessible(true);
        $baseAdapter = $base->getValue($adapter);

        $clientProp = (new \ReflectionClass($baseAdapter))->getProperty('client');
        $clientProp->setAccessible(true);
        /** @var ObsClient|m\MockInterface $client */
        $client = $clientProp->getValue($baseAdapter);

        $client->shouldReceive('getObject')->once()->andReturn(['Body' => 'abc']);

        self::assertSame('abc', $adapter->read('foo.txt'));
    }

    public function test_visibility_helpers(): void
    {
        $adapter = $this->makeLaravelAdapterWithMock();
        $refLaravel = new \ReflectionClass($adapter);
        $base = $refLaravel->getProperty('adapter');
        $base->setAccessible(true);
        $baseAdapter = $base->getValue($adapter);

        $clientProp = (new \ReflectionClass($baseAdapter))->getProperty('client');
        $clientProp->setAccessible(true);
        /** @var ObsClient|m\MockInterface $client */
        $client = $clientProp->getValue($baseAdapter);

        $client->shouldReceive('getObjectAcl')->once()->andReturn(['Grants' => []]);

        $attributes = $adapter->visibility('foo.txt');
        self::assertInstanceOf(FileAttributes::class, $attributes);
    }
}
