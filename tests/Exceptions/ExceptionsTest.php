<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests\Exceptions;

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
    public function test_base_exception_implements_filesystem_exception(): void
    {
        $e = new HuaweiObsException('msg');
        $this->assertInstanceOf(FilesystemException::class, $e);
        $this->assertSame('msg', $e->getMessage());
    }

    public function test_factories_produce_messages(): void
    {
        $e1 = UnableToCreatePostSignature::forLocation('a.txt');
        $this->assertStringContainsString('a.txt', $e1->getMessage());

        $e2 = UnableToCreateSignedUrl::forLocation('b.txt');
        $this->assertStringContainsString('b.txt', $e2->getMessage());

        $e3 = UnableToDeleteObjectTags::forLocation('c.txt');
        $this->assertStringContainsString('c.txt', $e3->getMessage());

        $e4 = UnableToGetObjectTags::forLocation('d.txt');
        $this->assertStringContainsString('d.txt', $e4->getMessage());

        $e5 = UnableToRestoreObject::forLocation('e.txt');
        $this->assertStringContainsString('e.txt', $e5->getMessage());

        $e6 = UnableToSetObjectTags::forLocation('f.txt');
        $this->assertStringContainsString('f.txt', $e6->getMessage());
    }
}
