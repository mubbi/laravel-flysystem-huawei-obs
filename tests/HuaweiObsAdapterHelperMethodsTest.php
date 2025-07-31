<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests;

use LaravelFlysystemHuaweiObs\HuaweiObsAdapter;
use League\Flysystem\Visibility;
use PHPUnit\Framework\TestCase;

class HuaweiObsAdapterHelperMethodsTest extends TestCase
{
    private HuaweiObsAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new HuaweiObsAdapter(
            'test-key',
            'test-secret',
            'test-bucket',
            'https://obs.test.com',
            null,
            null,
            null,
            3,
            1,
            false,
            false,
            true
        );
    }

    public function test_get_key_without_prefix(): void
    {
        $reflection = new \ReflectionClass($this->adapter);
        $method = $reflection->getMethod('getKey');
        $method->setAccessible(true);

        $result = $method->invoke($this->adapter, 'test/path.txt');
        $this->assertEquals('test/path.txt', $result);

        $result = $method->invoke($this->adapter, '/test/path.txt');
        $this->assertEquals('test/path.txt', $result);

        $result = $method->invoke($this->adapter, '///test/path.txt');
        $this->assertEquals('test/path.txt', $result);
    }

    public function test_get_key_with_prefix(): void
    {
        $adapter = new HuaweiObsAdapter(
            'test-key',
            'test-secret',
            'test-bucket',
            'https://obs.test.com',
            'my-prefix',
            null,
            null,
            3,
            1,
            false,
            false,
            true
        );

        $reflection = new \ReflectionClass($adapter);
        $method = $reflection->getMethod('getKey');
        $method->setAccessible(true);

        $result = $method->invoke($adapter, 'test/path.txt');
        $this->assertEquals('my-prefix/test/path.txt', $result);

        $result = $method->invoke($adapter, '/test/path.txt');
        $this->assertEquals('my-prefix/test/path.txt', $result);

        $result = $method->invoke($adapter, '///test/path.txt');
        $this->assertEquals('my-prefix/test/path.txt', $result);
    }

    public function test_get_relative_path_without_prefix(): void
    {
        $reflection = new \ReflectionClass($this->adapter);
        $method = $reflection->getMethod('getRelativePath');
        $method->setAccessible(true);

        $result = $method->invoke($this->adapter, 'test/path.txt');
        $this->assertEquals('/test/path.txt', $result);

        $result = $method->invoke($this->adapter, 'test/path.txt');
        $this->assertEquals('/test/path.txt', $result);
    }

    public function test_get_relative_path_with_prefix(): void
    {
        $adapter = new HuaweiObsAdapter(
            'test-key',
            'test-secret',
            'test-bucket',
            'https://obs.test.com',
            'my-prefix',
            null,
            null,
            3,
            1,
            false,
            false,
            true
        );

        $reflection = new \ReflectionClass($adapter);
        $method = $reflection->getMethod('getRelativePath');
        $method->setAccessible(true);

        $result = $method->invoke($adapter, 'my-prefix/test/path.txt');
        $this->assertEquals('/test/path.txt', $result);

        $result = $method->invoke($adapter, 'my-prefix/test/path.txt');
        $this->assertEquals('/test/path.txt', $result);

        $result = $method->invoke($adapter, 'my-prefix/');
        $this->assertEquals('/', $result);
    }

    public function test_visibility_to_acl(): void
    {
        $reflection = new \ReflectionClass($this->adapter);
        $method = $reflection->getMethod('visibilityToAcl');
        $method->setAccessible(true);

        $result = $method->invoke($this->adapter, Visibility::PUBLIC);
        $this->assertEquals('public-read', $result);

        $result = $method->invoke($this->adapter, Visibility::PRIVATE);
        $this->assertEquals('private', $result);

        $result = $method->invoke($this->adapter, 'unknown');
        $this->assertEquals('private', $result);
    }

    public function test_acl_to_visibility_with_public_read(): void
    {
        $reflection = new \ReflectionClass($this->adapter);
        $method = $reflection->getMethod('aclToVisibility');
        $method->setAccessible(true);

        $grants = [
            [
                'Grantee' => ['URI' => 'http://acs.amazonaws.com/groups/global/AllUsers'],
                'Permission' => 'READ',
            ],
        ];

        $result = $method->invoke($this->adapter, $grants);
        $this->assertEquals(Visibility::PUBLIC, $result);
    }

    public function test_acl_to_visibility_with_read_acp(): void
    {
        $reflection = new \ReflectionClass($this->adapter);
        $method = $reflection->getMethod('aclToVisibility');
        $method->setAccessible(true);

        $grants = [
            [
                'Grantee' => ['URI' => 'http://acs.amazonaws.com/groups/global/AllUsers'],
                'Permission' => 'READ_ACP',
            ],
        ];

        $result = $method->invoke($this->adapter, $grants);
        $this->assertEquals(Visibility::PUBLIC, $result);
    }

    public function test_acl_to_visibility_with_private(): void
    {
        $reflection = new \ReflectionClass($this->adapter);
        $method = $reflection->getMethod('aclToVisibility');
        $method->setAccessible(true);

        $grants = [
            [
                'Grantee' => ['URI' => 'http://acs.amazonaws.com/groups/global/AllUsers'],
                'Permission' => 'WRITE',
            ],
        ];

        $result = $method->invoke($this->adapter, $grants);
        $this->assertEquals(Visibility::PRIVATE, $result);
    }

    public function test_acl_to_visibility_with_unknown_uri(): void
    {
        $reflection = new \ReflectionClass($this->adapter);
        $method = $reflection->getMethod('aclToVisibility');
        $method->setAccessible(true);

        $grants = [
            [
                'Grantee' => ['URI' => 'http://unknown.com/groups/AllUsers'],
                'Permission' => 'READ',
            ],
        ];

        $result = $method->invoke($this->adapter, $grants);
        $this->assertEquals(Visibility::PRIVATE, $result);
    }

    public function test_acl_to_visibility_with_empty_grants(): void
    {
        $reflection = new \ReflectionClass($this->adapter);
        $method = $reflection->getMethod('aclToVisibility');
        $method->setAccessible(true);

        $result = $method->invoke($this->adapter, []);
        $this->assertEquals(Visibility::PRIVATE, $result);
    }

    public function test_acl_to_visibility_with_missing_grantee_uri(): void
    {
        $reflection = new \ReflectionClass($this->adapter);
        $method = $reflection->getMethod('aclToVisibility');
        $method->setAccessible(true);

        $grants = [
            [
                'Grantee' => ['ID' => 'some-id'],
                'Permission' => 'READ',
            ],
        ];

        $result = $method->invoke($this->adapter, $grants);
        $this->assertEquals(Visibility::PRIVATE, $result);
    }

    public function test_acl_to_visibility_with_missing_permission(): void
    {
        $reflection = new \ReflectionClass($this->adapter);
        $method = $reflection->getMethod('aclToVisibility');
        $method->setAccessible(true);

        $grants = [
            [
                'Grantee' => ['URI' => 'http://acs.amazonaws.com/groups/global/AllUsers'],
                // Missing permission
            ],
        ];

        $result = $method->invoke($this->adapter, $grants);
        $this->assertEquals(Visibility::PRIVATE, $result);
    }
}
