<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests\Console;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use LaravelFlysystemHuaweiObs\Console\TestHuaweiObsCommand;
use LaravelFlysystemHuaweiObs\HuaweiObsAdapter;
use LaravelFlysystemHuaweiObs\HuaweiObsServiceProvider;
use Mockery;
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

    public function test_command_has_signature(): void
    {
        $command = new TestHuaweiObsCommand;

        // Use reflection to access the protected signature property
        $reflection = new \ReflectionClass($command);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);
        $signature = $signatureProperty->getValue($command);

        $this->assertStringContainsString('huawei-obs:test', $signature);
        $this->assertStringContainsString('--disk=', $signature);
        $this->assertStringContainsString('--write-test', $signature);
        $this->assertStringContainsString('--read-test', $signature);
        $this->assertStringContainsString('--delete-test', $signature);
    }

    public function test_get_adapter_with_valid_adapter(): void
    {
        $command = new TestHuaweiObsCommand;

        // Mock the HuaweiObsAdapter
        $mockAdapter = Mockery::mock(HuaweiObsAdapter::class);

        // Mock the Filesystem
        $mockDisk = Mockery::mock(Filesystem::class);
        $mockDisk->shouldReceive('getDriver->getAdapter')->andReturn($mockAdapter);

        // Use reflection to access the private getAdapter method
        $reflection = new \ReflectionClass($command);
        $getAdapterMethod = $reflection->getMethod('getAdapter');
        $getAdapterMethod->setAccessible(true);

        $result = $getAdapterMethod->invoke($command, $mockDisk);

        $this->assertSame($mockAdapter, $result);
    }

    public function test_get_adapter_with_invalid_adapter(): void
    {
        $command = new TestHuaweiObsCommand;

        // Mock a non-HuaweiObsAdapter
        $mockAdapter = Mockery::mock('SomeOtherAdapter');

        // Mock the Filesystem
        $mockDisk = Mockery::mock(Filesystem::class);
        $mockDisk->shouldReceive('getDriver->getAdapter')->andReturn($mockAdapter);

        // Use reflection to access the private getAdapter method
        $reflection = new \ReflectionClass($command);
        $getAdapterMethod = $reflection->getMethod('getAdapter');
        $getAdapterMethod->setAccessible(true);

        $result = $getAdapterMethod->invoke($command, $mockDisk);

        $this->assertNull($result);
    }

    public function test_handle_method_exists(): void
    {
        $command = new TestHuaweiObsCommand;

        // Use reflection to check that the handle method exists and is public
        $reflection = new \ReflectionClass($command);
        $handleMethod = $reflection->getMethod('handle');

        $this->assertTrue($handleMethod->isPublic());
        $returnType = $handleMethod->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('int', $returnType->getName());
    }

    public function test_private_methods_exist(): void
    {
        $command = new TestHuaweiObsCommand;

        // Use reflection to check that all private methods exist
        $reflection = new \ReflectionClass($command);

        $expectedMethods = [
            'getAdapter',
            'testAuthentication',
            'testWriteOperations',
            'testReadOperations',
            'testDeleteOperations',
        ];

        foreach ($expectedMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPrivate(), "Method {$methodName} should be private");
        }
    }

    public function test_command_has_correct_properties(): void
    {
        $command = new TestHuaweiObsCommand;

        // Use reflection to check the command properties
        $reflection = new \ReflectionClass($command);

        // Check signature property
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);
        $signature = $signatureProperty->getValue($command);

        $this->assertStringContainsString('huawei-obs:test', $signature);
        $this->assertStringContainsString('{--disk=', $signature);
        $this->assertStringContainsString('{--write-test', $signature);
        $this->assertStringContainsString('{--read-test', $signature);
        $this->assertStringContainsString('{--delete-test', $signature);

        // Check description property
        $descriptionProperty = $reflection->getProperty('description');
        $descriptionProperty->setAccessible(true);
        $description = $descriptionProperty->getValue($command);

        $this->assertEquals('Test Huawei OBS connectivity and basic operations', $description);
    }

    public function test_command_method_signatures(): void
    {
        $command = new TestHuaweiObsCommand;

        // Use reflection to check method signatures
        $reflection = new \ReflectionClass($command);

        // Check handle method signature
        $handleMethod = $reflection->getMethod('handle');
        $returnType = $handleMethod->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('int', $returnType->getName());
        $this->assertEquals(0, $handleMethod->getNumberOfParameters());

        // Check private methods exist and are private
        $privateMethods = ['getAdapter', 'testAuthentication', 'testWriteOperations', 'testReadOperations', 'testDeleteOperations'];

        foreach ($privateMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPrivate(), "Method {$methodName} should be private");
        }
    }

    public function test_test_authentication_success(): void
    {
        $command = $this->getMockBuilder(TestHuaweiObsCommand::class)
            ->onlyMethods(['info', 'error'])
            ->getMock();

        $mockAdapter = Mockery::mock(HuaweiObsAdapter::class);
        $mockAdapter->shouldReceive('refreshAuthentication')
            ->once()
            ->andReturn(true);

        $command->expects($this->exactly(2))
            ->method('info');

        $command->expects($this->never())
            ->method('error');

        // Use reflection to access the private testAuthentication method
        $reflection = new \ReflectionClass($command);
        $testAuthMethod = $reflection->getMethod('testAuthentication');
        $testAuthMethod->setAccessible(true);

        $testAuthMethod->invoke($command, $mockAdapter);
    }

    public function test_test_authentication_failure(): void
    {
        $command = $this->getMockBuilder(TestHuaweiObsCommand::class)
            ->onlyMethods(['info', 'error'])
            ->getMock();

        $mockAdapter = Mockery::mock(HuaweiObsAdapter::class);
        $mockAdapter->shouldReceive('refreshAuthentication')
            ->once()
            ->andThrow(new \Exception('Auth failed'));

        $command->expects($this->once())
            ->method('info');

        $command->expects($this->once())
            ->method('error');

        // Use reflection to access the private testAuthentication method
        $reflection = new \ReflectionClass($command);
        $testAuthMethod = $reflection->getMethod('testAuthentication');
        $testAuthMethod->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Auth failed');

        $testAuthMethod->invoke($command, $mockAdapter);
    }

    public function test_test_write_operations_success(): void
    {
        $command = $this->getMockBuilder(TestHuaweiObsCommand::class)
            ->onlyMethods(['info', 'error'])
            ->getMock();

        $mockDisk = Mockery::mock(Filesystem::class);
        $mockAdapter = Mockery::mock(HuaweiObsAdapter::class);

        $mockDisk->shouldReceive('put')
            ->once()
            ->andReturn(true);

        $mockAdapter->shouldReceive('createSignedUrl')
            ->once()
            ->andReturn('https://example.com/signed-url');

        $mockAdapter->shouldReceive('createPostSignature')
            ->once()
            ->andReturn(['signature' => 'test-signature']);

        $mockAdapter->shouldReceive('setObjectTags')
            ->once()
            ->andReturn(true);

        $command->expects($this->exactly(6))
            ->method('info');

        $command->expects($this->never())
            ->method('error');

        // Use reflection to access the private testWriteOperations method
        $reflection = new \ReflectionClass($command);
        $testWriteMethod = $reflection->getMethod('testWriteOperations');
        $testWriteMethod->setAccessible(true);

        $testWriteMethod->invoke($command, $mockDisk, $mockAdapter);
    }

    public function test_test_write_operations_failure(): void
    {
        $command = $this->getMockBuilder(TestHuaweiObsCommand::class)
            ->onlyMethods(['info', 'error'])
            ->getMock();

        $mockDisk = Mockery::mock(Filesystem::class);
        $mockAdapter = Mockery::mock(HuaweiObsAdapter::class);

        $mockDisk->shouldReceive('put')
            ->once()
            ->andThrow(new \Exception('Write failed'));

        $command->expects($this->once())
            ->method('info');

        $command->expects($this->once())
            ->method('error');

        // Use reflection to access the private testWriteOperations method
        $reflection = new \ReflectionClass($command);
        $testWriteMethod = $reflection->getMethod('testWriteOperations');
        $testWriteMethod->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Write failed');

        $testWriteMethod->invoke($command, $mockDisk, $mockAdapter);
    }

    public function test_test_read_operations_success(): void
    {
        $command = $this->getMockBuilder(TestHuaweiObsCommand::class)
            ->onlyMethods(['info', 'error'])
            ->getMock();

        $mockDisk = Mockery::mock(Filesystem::class);
        $mockAdapter = Mockery::mock(HuaweiObsAdapter::class);

        $testContent = 'Test content for reading';

        $mockDisk->shouldReceive('put')
            ->once()
            ->andReturn(true);

        $mockDisk->shouldReceive('get')
            ->once()
            ->andReturn($testContent);

        $mockDisk->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        $mockDisk->shouldReceive('size')
            ->once()
            ->andReturn(strlen($testContent));

        $mockAdapter->shouldReceive('getObjectTags')
            ->once()
            ->andReturn(['test' => 'value']);

        $command->expects($this->exactly(6))
            ->method('info');

        $command->expects($this->never())
            ->method('error');

        // Use reflection to access the private testReadOperations method
        $reflection = new \ReflectionClass($command);
        $testReadMethod = $reflection->getMethod('testReadOperations');
        $testReadMethod->setAccessible(true);

        $testReadMethod->invoke($command, $mockDisk, $mockAdapter);
    }

    public function test_test_read_operations_content_mismatch(): void
    {
        $command = $this->getMockBuilder(TestHuaweiObsCommand::class)
            ->onlyMethods(['info', 'error'])
            ->getMock();

        $mockDisk = Mockery::mock(Filesystem::class);
        $mockAdapter = Mockery::mock(HuaweiObsAdapter::class);

        $mockDisk->shouldReceive('put')
            ->once()
            ->andReturn(true);

        $mockDisk->shouldReceive('get')
            ->once()
            ->andReturn('Different content');

        $command->expects($this->once())
            ->method('info');

        $command->expects($this->once())
            ->method('error');

        // Use reflection to access the private testReadOperations method
        $reflection = new \ReflectionClass($command);
        $testReadMethod = $reflection->getMethod('testReadOperations');
        $testReadMethod->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Content mismatch');

        $testReadMethod->invoke($command, $mockDisk, $mockAdapter);
    }

    public function test_test_read_operations_existence_failure(): void
    {
        $command = $this->getMockBuilder(TestHuaweiObsCommand::class)
            ->onlyMethods(['info', 'error'])
            ->getMock();

        $mockDisk = Mockery::mock(Filesystem::class);
        $mockAdapter = Mockery::mock(HuaweiObsAdapter::class);

        $testContent = 'Test content for reading';

        $mockDisk->shouldReceive('put')
            ->once()
            ->andReturn(true);

        $mockDisk->shouldReceive('get')
            ->once()
            ->andReturn($testContent);

        $mockDisk->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $command->expects($this->exactly(2))
            ->method('info');

        $command->expects($this->once())
            ->method('error');

        // Use reflection to access the private testReadOperations method
        $reflection = new \ReflectionClass($command);
        $testReadMethod = $reflection->getMethod('testReadOperations');
        $testReadMethod->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File existence check failed');

        $testReadMethod->invoke($command, $mockDisk, $mockAdapter);
    }

    public function test_test_read_operations_size_failure(): void
    {
        $command = $this->getMockBuilder(TestHuaweiObsCommand::class)
            ->onlyMethods(['info', 'error'])
            ->getMock();

        $mockDisk = Mockery::mock(Filesystem::class);
        $mockAdapter = Mockery::mock(HuaweiObsAdapter::class);

        $testContent = 'Test content for reading';

        $mockDisk->shouldReceive('put')
            ->once()
            ->andReturn(true);

        $mockDisk->shouldReceive('get')
            ->once()
            ->andReturn($testContent);

        $mockDisk->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        $mockDisk->shouldReceive('size')
            ->once()
            ->andReturn(999); // Wrong size

        $command->expects($this->exactly(3))
            ->method('info');

        $command->expects($this->once())
            ->method('error');

        // Use reflection to access the private testReadOperations method
        $reflection = new \ReflectionClass($command);
        $testReadMethod = $reflection->getMethod('testReadOperations');
        $testReadMethod->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File size check failed');

        $testReadMethod->invoke($command, $mockDisk, $mockAdapter);
    }

    public function test_test_delete_operations_success(): void
    {
        $command = $this->getMockBuilder(TestHuaweiObsCommand::class)
            ->onlyMethods(['info', 'error'])
            ->getMock();

        $mockDisk = Mockery::mock(Filesystem::class);
        $mockAdapter = Mockery::mock(HuaweiObsAdapter::class);

        $mockDisk->shouldReceive('put')
            ->once()
            ->andReturn(true);

        $mockDisk->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        $mockDisk->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $command->expects($this->exactly(4))
            ->method('info');

        $command->expects($this->never())
            ->method('error');

        // Use reflection to access the private testDeleteOperations method
        $reflection = new \ReflectionClass($command);
        $testDeleteMethod = $reflection->getMethod('testDeleteOperations');
        $testDeleteMethod->setAccessible(true);

        $testDeleteMethod->invoke($command, $mockDisk, $mockAdapter);
    }

    public function test_test_delete_operations_deletion_failure(): void
    {
        $command = $this->getMockBuilder(TestHuaweiObsCommand::class)
            ->onlyMethods(['info', 'error'])
            ->getMock();

        $mockDisk = Mockery::mock(Filesystem::class);
        $mockAdapter = Mockery::mock(HuaweiObsAdapter::class);

        $mockDisk->shouldReceive('put')
            ->once()
            ->andReturn(true);

        $mockDisk->shouldReceive('delete')
            ->once()
            ->andReturn(false);

        $command->expects($this->once())
            ->method('info');

        $command->expects($this->once())
            ->method('error');

        // Use reflection to access the private testDeleteOperations method
        $reflection = new \ReflectionClass($command);
        $testDeleteMethod = $reflection->getMethod('testDeleteOperations');
        $testDeleteMethod->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File deletion failed');

        $testDeleteMethod->invoke($command, $mockDisk, $mockAdapter);
    }

    public function test_test_delete_operations_existence_verification_failure(): void
    {
        $command = $this->getMockBuilder(TestHuaweiObsCommand::class)
            ->onlyMethods(['info', 'error'])
            ->getMock();

        $mockDisk = Mockery::mock(Filesystem::class);
        $mockAdapter = Mockery::mock(HuaweiObsAdapter::class);

        $mockDisk->shouldReceive('put')
            ->once()
            ->andReturn(true);

        $mockDisk->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        $mockDisk->shouldReceive('exists')
            ->once()
            ->andReturn(true); // File still exists

        $command->expects($this->exactly(2))
            ->method('info');

        $command->expects($this->once())
            ->method('error');

        // Use reflection to access the private testDeleteOperations method
        $reflection = new \ReflectionClass($command);
        $testDeleteMethod = $reflection->getMethod('testDeleteOperations');
        $testDeleteMethod->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File still exists after deletion');

        $testDeleteMethod->invoke($command, $mockDisk, $mockAdapter);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
