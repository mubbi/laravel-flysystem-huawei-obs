<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests;

use LaravelFlysystemHuaweiObs\HttpClientFactory;
use PHPUnit\Framework\TestCase;

class CompatibilityTest extends TestCase
{
    public function test_http_client_factory_detects_guzzle_version(): void
    {
        $version = HttpClientFactory::detectGuzzleVersion();

        $this->assertContains($version, ['v6', 'v7', 'v8']);
        
        // Additional test to ensure the version is a valid string
        $this->assertIsString($version);
        $this->assertMatchesRegularExpression('/^v[678]$/', $version);
    }

    public function test_http_client_factory_creates_client(): void
    {
        $config = [
            'timeout' => 60,
            'connect_timeout' => 20,
            'verify' => false,
        ];

        $client = HttpClientFactory::create($config);

        $this->assertNotNull($client);
        $this->assertInstanceOf(\GuzzleHttp\ClientInterface::class, $client);
    }

    public function test_http_client_factory_handles_empty_config(): void
    {
        $client = HttpClientFactory::create([]);

        $this->assertNull($client);
    }

    public function test_http_client_factory_handles_null_config(): void
    {
        $client = HttpClientFactory::create(null);

        $this->assertNull($client);
    }

    public function test_http_client_factory_handles_empty_array_config(): void
    {
        $client = HttpClientFactory::create([]);

        $this->assertNull($client);
    }

    public function test_flysystem_version_detection(): void
    {
        // Test that we can detect Flysystem v3
        $this->assertTrue(interface_exists('League\Flysystem\FilesystemAdapter'));

        // Test that we can detect Flysystem v2 (if available)
        if (interface_exists('League\Flysystem\AdapterInterface')) {
            $this->assertTrue(interface_exists('League\Flysystem\AdapterInterface'));
        }
    }

    public function test_guzzle_version_detection(): void
    {
        // Test that Guzzle is available
        $this->assertTrue(class_exists('GuzzleHttp\Client'));

        // Test that we can create a client
        $client = new \GuzzleHttp\Client;
        $this->assertInstanceOf(\GuzzleHttp\ClientInterface::class, $client);
    }
}
