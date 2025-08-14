<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests\Adapter;

use LaravelFlysystemHuaweiObs\Exceptions\UnableToCreatePostSignature;
use LaravelFlysystemHuaweiObs\Exceptions\UnableToCreateSignedUrl;
use Mockery as m;
use Obs\ObsClient;
use Obs\ObsException;
use PHPUnit\Framework\TestCase;

class AbstractHuaweiObsAdapterTest extends TestCase
{
    /**
     * Create a concrete adapter for testing protected helpers
     *
     * @param  array<string,mixed>  $configOverrides
     */
    private function makeConcrete(array $configOverrides = []): ConcreteTestHuaweiObsAdapter
    {
        $client = m::mock(ObsClient::class);

        $concrete = new ConcreteTestHuaweiObsAdapter(
            'key',
            'secret',
            'bucket',
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

        // Inject mocked client and mark authenticated
        $ref = new \ReflectionClass($concrete);
        $clientProp = $ref->getProperty('client');
        $clientProp->setAccessible(true);
        $clientProp->setValue($concrete, $client);

        $authProp = $ref->getProperty('authenticated');
        $authProp->setAccessible(true);
        $authProp->setValue($concrete, true);
        $authExpProp = $ref->getProperty('authCacheExpiry');
        $authExpProp->setAccessible(true);
        $authExpProp->setValue($concrete, time() + 3600);

        return $concrete;
    }

    public function test_visibility_conversions(): void
    {
        $adapter = $this->makeConcrete();
        self::assertSame('public-read', $adapter->callVisibilityToAcl('public'));
        self::assertSame('private', $adapter->callVisibilityToAcl('anything-else'));

        $vis = $adapter->callAclToVisibility([
            ['Grantee' => ['URI' => 'http://acs.amazonaws.com/groups/global/AllUsers'], 'Permission' => 'READ'],
        ]);
        self::assertSame('public', $vis);

        $vis2 = $adapter->callAclToVisibility([]);
        self::assertSame('private', $vis2);
    }

    public function test_key_helpers(): void
    {
        $adapter = $this->makeConcrete();
        self::assertSame('file.txt', $adapter->callGetKey('/file.txt'));
        self::assertSame('/file.txt', $adapter->callGetRelativePath('file.txt'));
    }

    public function test_error_code_extraction_and_predicates(): void
    {
        $adapter = $this->makeConcrete();

        $e = m::mock(ObsException::class);
        $e->shouldReceive('getExceptionCode')->andReturn('NoSuchKey');
        $e->shouldReceive('getMessage')->andReturn('NoSuchKey');

        self::assertSame('NoSuchKey', $adapter->callExtractErrorCode($e));
        self::assertTrue($adapter->callIsNotFoundError($e));
        self::assertFalse($adapter->callIsAuthError($e));
        self::assertFalse($adapter->callIsBucketError($e));
    }

    public function test_create_signed_url_and_post_signature_wrap_errors(): void
    {
        $adapter = $this->makeConcrete();

        // swap in client that will throw
        $ref = new \ReflectionClass($adapter);
        /** @var ObsClient|m\MockInterface $client */
        $client = m::mock(ObsClient::class);
        $obsEx1 = m::mock(ObsException::class);
        $obsEx1->shouldReceive('getExceptionCode')->zeroOrMoreTimes()->andReturn(null);
        $obsEx1->shouldReceive('getMessage')->zeroOrMoreTimes()->andReturn('error');
        $client->shouldReceive('createSignedUrl')->andThrow($obsEx1);

        $obsEx2 = m::mock(ObsException::class);
        $obsEx2->shouldReceive('getExceptionCode')->zeroOrMoreTimes()->andReturn(null);
        $obsEx2->shouldReceive('getMessage')->zeroOrMoreTimes()->andReturn('error');
        $client->shouldReceive('createPostSignature')->andThrow($obsEx2);
        $clientProp = $ref->getProperty('client');
        $clientProp->setAccessible(true);
        $clientProp->setValue($adapter, $client);

        $this->expectException(UnableToCreateSignedUrl::class);
        $adapter->createSignedUrl('a.txt');

        $this->expectException(UnableToCreatePostSignature::class);
        $adapter->createPostSignature('a.txt');
    }
}
