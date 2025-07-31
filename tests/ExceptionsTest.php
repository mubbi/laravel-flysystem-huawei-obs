<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests;

use LaravelFlysystemHuaweiObs\Exceptions\HuaweiObsException;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToCreatePostSignature;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToCreateSignedUrl;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToDeleteObjectTags;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToGetObjectTags;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToRestoreObject;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToSetObjectTags;
use League\Flysystem\FilesystemException;
use PHPUnit\Framework\TestCase;

class ExceptionsTest extends TestCase
{
    public function test_huawei_obs_exception_implements_filesystem_exception(): void
    {
        $exception = new HuaweiObsException('Test message');

        $this->assertInstanceOf(FilesystemException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function test_unable_to_create_signed_url_exception(): void
    {
        $previousException = new \Exception('Previous error');
        $exception = UnableToCreateSignedUrl::forLocation('test/path.txt', $previousException);

        $this->assertInstanceOf(HuaweiObsException::class, $exception);
        $this->assertEquals('Unable to create signed URL for location: test/path.txt', $exception->getMessage());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function test_unable_to_create_signed_url_exception_without_previous(): void
    {
        $exception = UnableToCreateSignedUrl::forLocation('test/path.txt');

        $this->assertInstanceOf(HuaweiObsException::class, $exception);
        $this->assertEquals('Unable to create signed URL for location: test/path.txt', $exception->getMessage());
        $this->assertNull($exception->getPrevious());
    }

    public function test_unable_to_create_post_signature_exception(): void
    {
        $previousException = new \Exception('Previous error');
        $exception = UnableToCreatePostSignature::forLocation('test/path.txt', $previousException);

        $this->assertInstanceOf(HuaweiObsException::class, $exception);
        $this->assertEquals('Unable to create post signature for location: test/path.txt', $exception->getMessage());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function test_unable_to_set_object_tags_exception(): void
    {
        $previousException = new \Exception('Previous error');
        $exception = UnableToSetObjectTags::forLocation('test/path.txt', $previousException);

        $this->assertInstanceOf(HuaweiObsException::class, $exception);
        $this->assertEquals('Unable to set object tags for location: test/path.txt', $exception->getMessage());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function test_unable_to_get_object_tags_exception(): void
    {
        $previousException = new \Exception('Previous error');
        $exception = UnableToGetObjectTags::forLocation('test/path.txt', $previousException);

        $this->assertInstanceOf(HuaweiObsException::class, $exception);
        $this->assertEquals('Unable to get object tags for location: test/path.txt', $exception->getMessage());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function test_unable_to_delete_object_tags_exception(): void
    {
        $previousException = new \Exception('Previous error');
        $exception = UnableToDeleteObjectTags::forLocation('test/path.txt', $previousException);

        $this->assertInstanceOf(HuaweiObsException::class, $exception);
        $this->assertEquals('Unable to delete object tags for location: test/path.txt', $exception->getMessage());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function test_unable_to_restore_object_exception(): void
    {
        $previousException = new \Exception('Previous error');
        $exception = UnableToRestoreObject::forLocation('test/path.txt', $previousException);

        $this->assertInstanceOf(HuaweiObsException::class, $exception);
        $this->assertEquals('Unable to restore object for location: test/path.txt', $exception->getMessage());
        $this->assertSame($previousException, $exception->getPrevious());
    }
}
