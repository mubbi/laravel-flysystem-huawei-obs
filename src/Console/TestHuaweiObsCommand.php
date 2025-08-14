<?php

declare(strict_types=1);

/**
 * Huawei OBS Test Command
 *
 * Artisan command to test Huawei OBS connectivity and operations.
 * Provides comprehensive testing of the Huawei OBS adapter functionality.
 *
 * @author  Mubbasher Ahmed <hello@mubbi.me>
 *
 * @link    https://mubbi.me
 *
 * @license MIT
 */

namespace LaravelFlysystemHuaweiObs\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use LaravelFlysystemHuaweiObs\HuaweiObsAdapter;

class TestHuaweiObsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'huawei-obs:test 
                            {--disk=huawei-obs : The disk to test}
                            {--write-test : Test write operations}
                            {--read-test : Test read operations}
                            {--delete-test : Test delete operations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Huawei OBS connectivity and basic operations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $diskNameOption = $this->option('disk');
        $diskName = is_string($diskNameOption) ? $diskNameOption : '';
        $testWrite = (bool) $this->option('write-test');
        $testRead = (bool) $this->option('read-test');
        $testDelete = (bool) $this->option('delete-test');

        // If no specific tests are requested, run all
        if (! $testWrite && ! $testRead && ! $testDelete) {
            $testWrite = $testRead = $testDelete = true;
        }

        $this->info("Testing Huawei OBS disk: {$diskName}");
        $this->newLine();

        try {
            $disk = Storage::disk($diskName);

            // Get the adapter to access advanced features
            $adapter = $this->getAdapter($disk);

            if (! $adapter) {
                $this->error("Disk '{$diskName}' is not a Huawei OBS disk");

                return 1;
            }

            // Test authentication
            $this->testAuthentication($adapter);

            // Test basic operations
            if ($testWrite) {
                $this->testWriteOperations($disk, $adapter);
            }

            if ($testRead) {
                $this->testReadOperations($disk, $adapter);
            }

            if ($testDelete) {
                $this->testDeleteOperations($disk, $adapter);
            }

            $this->newLine();
            $this->info('✅ All tests passed successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Test failed: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Get the Huawei OBS adapter from the disk
     *
     * @param  \Illuminate\Contracts\Filesystem\Filesystem  $disk
     */
    private function getAdapter($disk): ?HuaweiObsAdapter
    {
        // Access the underlying adapter (Illuminate FilesystemAdapter)
        if ($disk instanceof \Illuminate\Filesystem\FilesystemAdapter) {
            $adapter = $disk->getAdapter();
        } else {
            // Try via getDriver() for older Laravel versions
            $filesystem = method_exists($disk, 'getDriver') ? $disk->getDriver() : null;
            $adapter = is_object($filesystem) && method_exists($filesystem, 'getAdapter') ? $filesystem->getAdapter() : null;
        }

        if ($adapter instanceof HuaweiObsAdapter) {
            return $adapter;
        }

        return null;
    }

    /**
     * Test authentication
     */
    private function testAuthentication(HuaweiObsAdapter $adapter): void
    {
        $this->info('🔐 Testing authentication...');

        try {
            $adapter->refreshAuthentication();
            $this->info('  ✅ Authentication successful');
        } catch (\Exception $e) {
            $this->error('  ❌ Authentication failed: '.$e->getMessage());

            throw $e;
        }
    }

    /**
     * Test write operations
     *
     * @param  \Illuminate\Contracts\Filesystem\Filesystem  $disk
     */
    private function testWriteOperations($disk, HuaweiObsAdapter $adapter): void
    {
        $this->info('📝 Testing write operations...');

        $testFile = 'test-'.uniqid().'.txt';
        $testContent = 'Test content generated at '.now()->toISOString();

        try {
            // Test basic write
            $disk->put($testFile, $testContent);
            $this->info("  ✅ File written: {$testFile}");

            // Test signed URL creation
            $signedUrl = $adapter->createSignedUrl($testFile, 'GET', 3600);
            $this->info('  ✅ Signed URL created: '.substr($signedUrl, 0, 50).'...');

            // Test post signature creation
            $postSignature = $adapter->createPostSignature($testFile);
            $this->info('  ✅ Post signature created');

            // Test object tagging
            $tags = ['test' => 'true', 'created_at' => (string) now()->toISOString()];
            $adapter->setObjectTags($testFile, $tags);
            $this->info('  ✅ Object tags set');

            $this->info('  ✅ All write operations successful');
        } catch (\Exception $e) {
            $this->error('  ❌ Write operation failed: '.$e->getMessage());

            throw $e;
        }
    }

    /**
     * Test read operations
     *
     * @param  \Illuminate\Contracts\Filesystem\Filesystem  $disk
     */
    private function testReadOperations($disk, HuaweiObsAdapter $adapter): void
    {
        $this->info('📖 Testing read operations...');

        $testFile = 'test-'.uniqid().'.txt';
        $testContent = 'Test content for reading';

        try {
            // Write a test file first
            $disk->put($testFile, $testContent);

            // Test basic read
            $readContent = $disk->get($testFile);
            if ($readContent === $testContent) {
                $this->info("  ✅ File read successfully: {$testFile}");
            } else {
                throw new \Exception('Content mismatch');
            }

            // Test file existence
            if ($disk->exists($testFile)) {
                $this->info('  ✅ File existence check passed');
            } else {
                throw new \Exception('File existence check failed');
            }

            // Test file size
            $size = $disk->size($testFile);
            if ($size === strlen($testContent)) {
                $this->info("  ✅ File size check passed: {$size} bytes");
            } else {
                throw new \Exception('File size check failed');
            }

            // Test object tags
            $tags = $adapter->getObjectTags($testFile);
            $this->info('  ✅ Object tags retrieved');

            $this->info('  ✅ All read operations successful');
        } catch (\Exception $e) {
            $this->error('  ❌ Read operation failed: '.$e->getMessage());

            throw $e;
        }
    }

    /**
     * Test delete operations
     *
     * @param  \Illuminate\Contracts\Filesystem\Filesystem  $disk
     */
    private function testDeleteOperations($disk, HuaweiObsAdapter $adapter): void
    {
        $this->info('🗑️  Testing delete operations...');

        $testFile = 'test-'.uniqid().'.txt';
        $testContent = 'Test content for deletion';

        try {
            // Write a test file first
            $disk->put($testFile, $testContent);

            // Test file deletion
            if ($disk->delete($testFile)) {
                $this->info("  ✅ File deleted successfully: {$testFile}");
            } else {
                throw new \Exception('File deletion failed');
            }

            // Verify file is deleted
            if (! $disk->exists($testFile)) {
                $this->info('  ✅ File existence verification passed');
            } else {
                throw new \Exception('File still exists after deletion');
            }

            $this->info('  ✅ All delete operations successful');
        } catch (\Exception $e) {
            $this->error('  ❌ Delete operation failed: '.$e->getMessage());

            throw $e;
        }
    }
}
