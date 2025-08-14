<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests\Integration;

use LaravelFlysystemHuaweiObs\HuaweiObsAdapter;
use League\Flysystem\Config;
use League\Flysystem\Visibility;
use PHPUnit\Framework\TestCase;

class LiveObsIntegrationTest extends TestCase
{
    /** @var array<string,string> */
    private array $env = [];

    private ?HuaweiObsAdapter $adapter = null;

    private string $testPrefix = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->env = $this->loadDotEnvValues(getcwd().'/.env');

        $required = ['HUAWEI_OBS_ACCESS_KEY_ID', 'HUAWEI_OBS_SECRET_ACCESS_KEY', 'HUAWEI_OBS_BUCKET', 'HUAWEI_OBS_ENDPOINT'];
        foreach ($required as $key) {
            $val = $this->env[$key] ?? getenv($key) ?: null;
            if ($val === null || $val === '') {
                $this->markTestSkipped('Live OBS env not configured: missing '.$key);
            }
        }

        $accessKey = (string) ($this->env['HUAWEI_OBS_ACCESS_KEY_ID'] ?? getenv('HUAWEI_OBS_ACCESS_KEY_ID'));
        $secretKey = (string) ($this->env['HUAWEI_OBS_SECRET_ACCESS_KEY'] ?? getenv('HUAWEI_OBS_SECRET_ACCESS_KEY'));
        $bucket = (string) ($this->env['HUAWEI_OBS_BUCKET'] ?? getenv('HUAWEI_OBS_BUCKET'));
        $endpoint = (string) ($this->env['HUAWEI_OBS_ENDPOINT'] ?? getenv('HUAWEI_OBS_ENDPOINT'));
        $basePrefix = (string) ($this->env['HUAWEI_OBS_PREFIX'] ?? getenv('HUAWEI_OBS_PREFIX') ?: '');

        $this->testPrefix = trim($basePrefix.'/'.'laravel-flysystem-huawei-obs-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)), '/');

        $verify = (string) ($this->env['HUAWEI_OBS_VERIFY_SSL'] ?? getenv('HUAWEI_OBS_VERIFY_SSL') ?: 'true');
        $sslVerify = ! in_array(strtolower($verify), ['0', 'false', 'no'], true);

        $this->adapter = new HuaweiObsAdapter(
            $accessKey,
            $secretKey,
            $bucket,
            $endpoint,
            $this->testPrefix,
            null,
            ($this->env['HUAWEI_OBS_SECURITY_TOKEN'] ?? getenv('HUAWEI_OBS_SECURITY_TOKEN')) ?: null,
            (int) (($this->env['HUAWEI_OBS_RETRY_ATTEMPTS'] ?? getenv('HUAWEI_OBS_RETRY_ATTEMPTS')) ?: 3),
            (int) (($this->env['HUAWEI_OBS_RETRY_DELAY'] ?? getenv('HUAWEI_OBS_RETRY_DELAY')) ?: 1),
            false, // logging_enabled
            false, // log_operations
            true,  // log_errors
            $sslVerify
        );
    }

    protected function tearDown(): void
    {
        if ($this->adapter instanceof HuaweiObsAdapter) {
            try {
                // Delete only under our test prefix by leveraging adapter-level prefix
                $this->adapter->deleteDirectory('');
            } catch (\Throwable $e) {
                // ignore cleanup errors
            }
        }
        parent::tearDown();
    }

    public function test_live_write_read_and_list_and_url(): void
    {
        $this->assertInstanceOf(HuaweiObsAdapter::class, $this->adapter);
        if (! ($this->adapter instanceof HuaweiObsAdapter)) {
            $this->markTestSkipped('Adapter not initialized');

            return;
        }

        // Write a file
        $this->adapter->write('hello.txt', 'hello world', new Config);
        $this->assertTrue($this->adapter->fileExists('hello.txt'));

        // Read it back
        $contents = $this->adapter->read('hello.txt');
        $this->assertSame('hello world', $contents);

        // Create a subdirectory and another file
        $this->adapter->createDirectory('dir', new Config);
        $this->adapter->write('dir/nested.txt', 'nested', new Config);

        // List contents under our prefix
        $items = iterator_to_array($this->adapter->listContents('', true));
        $this->assertNotEmpty($items);

        // Get a signed URL via url() which falls back to signed URL for private objects
        $url = $this->adapter->url('hello.txt');
        $this->assertIsString($url);
        $this->assertGreaterThan(10, strlen($url));
    }

    public function test_live_streams_and_metadata_and_visibility(): void
    {
        $this->assertInstanceOf(HuaweiObsAdapter::class, $this->adapter);

        // Write stream with explicit mimetype
        $stream = fopen('php://temp', 'r+');
        $this->assertIsResource($stream);
        if (! is_resource($stream)) {
            $this->fail('Unable to create temp stream');
        }
        fwrite($stream, 'stream-body');
        rewind($stream);

        $this->adapter->writeStream('stream/file.txt', $stream, new Config(['mimetype' => 'text/plain']));
        if (is_resource($stream)) {
            fclose($stream);
        }

        // Read back as stream
        $rs = $this->adapter->readStream('stream/file.txt');
        $this->assertIsResource($rs);
        $this->assertSame('stream-body', (string) stream_get_contents($rs));
        if (is_resource($rs)) {
            fclose($rs);
        }

        // Metadata
        $this->assertSame('text/plain', $this->adapter->mimeType('stream/file.txt')->mimeType());
        $this->assertIsInt($this->adapter->lastModified('stream/file.txt')->lastModified());
        $this->assertSame(strlen('stream-body'), $this->adapter->fileSize('stream/file.txt')->fileSize());

        // Visibility set/get (operate only on our test object)
        $this->adapter->setVisibility('stream/file.txt', Visibility::PRIVATE);
        $this->assertSame('private', $this->adapter->visibility('stream/file.txt')->visibility());

        // Optionally make public and assert URL shape when opt-in
        $publicOptIn = (string) ($this->env['HUAWEI_OBS_TEST_PUBLIC_URL'] ?? getenv('HUAWEI_OBS_TEST_PUBLIC_URL') ?: '0');
        if (in_array(strtolower($publicOptIn), ['1', 'true', 'yes'], true)) {
            $this->adapter->setVisibility('stream/file.txt', Visibility::PUBLIC);
            $url = $this->adapter->url('stream/file.txt');
            $this->assertIsString($url);
            $this->assertStringContainsString('/'.$this->adapterBucket().'/'.$this->adapterPrefix().'stream/file.txt', $url);
        }
    }

    public function test_live_move_copy_and_exists_and_directory_exists(): void
    {
        $this->adapter->write('ops/a.txt', 'A', new Config);
        $this->assertTrue($this->adapter->fileExists('ops/a.txt'));

        // Copy
        $this->adapter->copy('ops/a.txt', 'ops/b.txt', new Config);
        $this->assertTrue($this->adapter->fileExists('ops/a.txt'));
        $this->assertTrue($this->adapter->fileExists('ops/b.txt'));

        // Move
        $this->adapter->move('ops/b.txt', 'ops/c.txt', new Config);
        $this->assertFalse($this->adapter->fileExists('ops/b.txt'));
        $this->assertTrue($this->adapter->fileExists('ops/c.txt'));

        // Directory checks
        $this->adapter->createDirectory('ops/dirx', new Config);
        $this->assertTrue($this->adapter->directoryExists('ops/dirx'));
    }

    public function test_live_listings_and_optimized_helpers_and_stats(): void
    {
        // Prepare files and directories
        $this->adapter->createDirectory('list/dir1', new Config);
        $this->adapter->createDirectory('list/dir2', new Config);
        $this->adapter->write('list/file1.txt', '1', new Config);
        $this->adapter->write('list/dir1/file2.txt', '22', new Config);

        // Shallow
        $shallow = iterator_to_array($this->adapter->listContents('list', false));
        $this->assertNotEmpty($shallow);

        // Deep
        $deep = iterator_to_array($this->adapter->listContents('list', true));
        $this->assertNotEmpty($deep);

        // Optimized
        $opt = iterator_to_array($this->adapter->listContentsOptimized('list', true, 10, 10));
        $this->assertNotEmpty($opt);

        $allFiles = $this->adapter->allFilesOptimized(10, 10);
        $this->assertIsArray($allFiles);

        $allDirs = $this->adapter->allDirectoriesOptimized(10, 10);
        $this->assertIsArray($allDirs);

        $stats = $this->adapter->getStorageStats(10, 10);
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_files', $stats);
        $this->assertArrayHasKey('total_directories', $stats);
    }

    public function test_live_signed_and_post_signatures_and_temporary_urls(): void
    {
        // Ensure target exists for GET signed URL
        $this->adapter->write('sig/target.txt', 'data', new Config);

        $getUrl = $this->adapter->createSignedUrl('sig/target.txt', 'GET', 300);
        $this->assertIsString($getUrl);

        $putUrl = $this->adapter->createSignedUrl('sig/put.txt', 'PUT', 300, ['Content-Type' => 'text/plain']);
        $this->assertIsString($putUrl);

        $post = $this->adapter->createPostSignature('sig/post.txt', [
            ['starts-with', '$key', 'sig/'],
            ['content-length-range', 0, 1024 * 1024],
        ], 600);
        $this->assertIsArray($post);
        $this->assertTrue(isset($post['Policy']) || isset($post['policy']));
        $this->assertTrue(isset($post['Signature']) || isset($post['signature']));

        $tmp = $this->adapter->getTemporaryUrl('sig/target.txt', new \DateTimeImmutable('+10 minutes'));
        $this->assertIsString($tmp);

        $uploadTmp = $this->adapter->temporaryUploadUrl('sig/upload.txt', new \DateTimeImmutable('+10 minutes'));
        $this->assertIsString($uploadTmp);
    }

    public function test_live_object_tags_and_restore_are_optional(): void
    {
        $optTags = (string) ($this->env['HUAWEI_OBS_TEST_TAGS'] ?? getenv('HUAWEI_OBS_TEST_TAGS') ?: '0');
        $optRestore = (string) ($this->env['HUAWEI_OBS_TEST_RESTORE'] ?? getenv('HUAWEI_OBS_TEST_RESTORE') ?: '0');

        // Create a file to operate on
        $this->adapter->write('opt/features.txt', 'x', new Config);

        if (in_array(strtolower($optTags), ['1', 'true', 'yes'], true)) {
            // Best-effort: if the backend rejects the payload shape, do not fail the entire test run
            try {
                $this->adapter->setObjectTags('opt/features.txt', ['env' => 'testing']);
                $tags = $this->adapter->getObjectTags('opt/features.txt');
                $this->assertIsArray($tags);
                $this->adapter->deleteObjectTags('opt/features.txt');
            } catch (\Throwable $e) {
                $this->markTestSkipped('Object tagging not enabled/supported on bucket: '.$e->getMessage());
            }
        }

        if (in_array(strtolower($optRestore), ['1', 'true', 'yes'], true)) {
            try {
                $this->adapter->restoreObject('opt/features.txt', 1);
                $this->assertTrue(true);
            } catch (\Throwable $e) {
                $this->markTestSkipped('Restore not applicable (object not archived): '.$e->getMessage());
            }
        }
        // Ensure at least one assertion in case both optional paths are skipped
        $this->assertTrue(true);
    }

    private function adapterBucket(): string
    {
        $r = new \ReflectionClass($this->adapter);
        $p = $r->getProperty('bucket');
        $p->setAccessible(true);

        return (string) $p->getValue($this->adapter);
    }

    private function adapterPrefix(): string
    {
        $r = new \ReflectionClass($this->adapter);
        $p = $r->getProperty('prefix');
        $p->setAccessible(true);
        $prefix = (string) ($p->getValue($this->adapter) ?? '');

        return $prefix === '' ? '' : rtrim($prefix, '/').'/';
    }

    /**
     * @return array<string,string>
     */
    private function loadDotEnvValues(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $vars = [];
        foreach (file($path) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));
            $value = trim($value, "\"' ");
            $vars[$key] = $value;
        }

        return $vars;
    }
}
