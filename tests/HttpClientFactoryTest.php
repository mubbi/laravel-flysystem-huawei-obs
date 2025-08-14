<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests;

use LaravelFlysystemHuaweiObs\HttpClientFactory;
use PHPUnit\Framework\TestCase;

class HttpClientFactoryTest extends TestCase
{
    public function test_create_returns_null_when_empty_config(): void
    {
        $client = HttpClientFactory::create([]);
        $this->assertNull($client);
    }

    public function test_create_returns_client_with_config(): void
    {
        $client = HttpClientFactory::create(['timeout' => 1, 'verify' => false]);
        $this->assertNotNull($client);
        $this->assertTrue(method_exists($client, 'getConfig'));
    }

    public function test_detect_guzzle_version_returns_string(): void
    {
        $version = HttpClientFactory::detectGuzzleVersion();
        $this->assertContains($version, ['v6', 'v7', 'v8']);
    }

    public function test_create_returns_null_when_null_config(): void
    {
        $client = HttpClientFactory::create(null);
        $this->assertNull($client);
    }

    public function test_create_applies_proxy_headers_and_options(): void
    {
        $config = [
            'timeout' => 5,
            'connect_timeout' => 2,
            'verify' => false,
            'proxy' => 'http://localhost:8080',
            'headers' => ['X-Test' => '1'],
            'http_errors' => false,
            'allow_redirects' => ['max' => 2],
        ];

        $client = HttpClientFactory::create($config);
        $this->assertNotNull($client);
        $this->assertSame(5, $client->getConfig('timeout'));
        $this->assertSame(2, $client->getConfig('connect_timeout'));
        $this->assertFalse($client->getConfig('verify'));
        $this->assertSame('http://localhost:8080', $client->getConfig('proxy'));
        $this->assertIsArray($client->getConfig('headers'));
        $this->assertSame('1', $client->getConfig('headers')['X-Test'] ?? null);
        $this->assertFalse($client->getConfig('http_errors'));
        $this->assertSame(['max' => 2], $client->getConfig('allow_redirects'));
    }

    public function test_create_uses_defaults_when_values_are_null(): void
    {
        $config = [
            'timeout' => null,
            'connect_timeout' => null,
            'verify' => null,
        ];

        $client = HttpClientFactory::create($config);
        $this->assertNotNull($client);
        $this->assertSame(30, $client->getConfig('timeout'));
        $this->assertSame(10, $client->getConfig('connect_timeout'));
        $this->assertTrue($client->getConfig('verify'));
        $this->assertNull($client->getConfig('proxy'));
        $this->assertIsArray($client->getConfig('headers'));
    }

    public function test_create_does_not_set_headers_when_not_array(): void
    {
        $config = [
            'headers' => 'invalid',
        ];

        $client = HttpClientFactory::create($config);
        $this->assertNotNull($client);
        $this->assertIsArray($client->getConfig('headers'));
        $this->assertArrayNotHasKey('invalid', $client->getConfig('headers'));
    }

    public function test_detect_guzzle_version_fallback_when_no_guzzle_class(): void
    {
        FunctionOverrideControl::reset();
        FunctionOverrideControl::$enabled = true;
        FunctionOverrideControl::$guzzleClientExists = false;

        $version = HttpClientFactory::detectGuzzleVersion();
        $this->assertSame('v7', $version);

        FunctionOverrideControl::reset();
    }

    public function test_detect_guzzle_version_reflection_path_v7(): void
    {
        // Force skip Composer\InstalledVersions path to hit reflection
        FunctionOverrideControl::reset();
        FunctionOverrideControl::$enabled = true;
        FunctionOverrideControl::$composerInstalledVersionsExists = false;
        FunctionOverrideControl::$guzzleClientExists = true;
        FunctionOverrideControl::$psr18InterfaceExists = false; // ensure not v8

        $version = HttpClientFactory::detectGuzzleVersion();
        $this->assertContains($version, ['v7', 'v8']); // depending on installed guzzle

        FunctionOverrideControl::reset();
    }

    public function test_detect_guzzle_version_env_override(): void
    {
        $prev = getenv('HUAWEI_OBS_HTTP_CLIENT_FACTORY_GUZZLE_VERSION');
        putenv('HUAWEI_OBS_HTTP_CLIENT_FACTORY_GUZZLE_VERSION=v6');

        $version = HttpClientFactory::detectGuzzleVersion();
        $this->assertSame('v6', $version);

        if ($prev === false) {
            putenv('HUAWEI_OBS_HTTP_CLIENT_FACTORY_GUZZLE_VERSION');
        } else {
            putenv('HUAWEI_OBS_HTTP_CLIENT_FACTORY_GUZZLE_VERSION='.$prev);
        }
    }
}
