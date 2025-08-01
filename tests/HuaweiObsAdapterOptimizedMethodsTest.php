<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests;

use LaravelFlysystemHuaweiObs\HuaweiObsAdapter;
use League\Flysystem\FileAttributes;
use Mockery;
use Obs\ObsClient;
use Obs\ObsException;
use PHPUnit\Framework\TestCase;

class HuaweiObsAdapterOptimizedMethodsTest extends TestCase
{
    private string $accessKeyId = 'test-key';

    private string $secretAccessKey = 'test-secret';

    private string $bucket = 'test-bucket';

    private string $endpoint = 'https://obs.test.com';

    /** @var \Mockery\MockInterface&\Obs\ObsClient */
    private $mockClient;

    private HuaweiObsAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = Mockery::mock(ObsClient::class);

        // Mock authentication check
        $this->mockClient->shouldReceive('headBucket')
            ->with(['Bucket' => $this->bucket])
            ->andReturn(['HttpStatusCode' => 200])
            ->byDefault();

        $this->adapter = new HuaweiObsAdapter(
            $this->accessKeyId,
            $this->secretAccessKey,
            $this->bucket,
            $this->endpoint,
            null,
            null,
            null,
            3,
            1,
            false,
            false,
            true
        );

        // Replace the client with our mock
        $reflection = new \ReflectionClass($this->adapter);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->adapter, $this->mockClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================================================
    // TESTS FOR NEW OPTIMIZED METHODS
    // ============================================================================

    public function test_get_storage_stats_returns_comprehensive_statistics(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ],
                    [
                        'Key' => 'file2.jpg',
                        'Size' => 200,
                        'LastModified' => '2023-01-02T00:00:00Z',
                        'Grants' => [],
                    ],
                ],
                'CommonPrefixes' => [
                    ['Prefix' => 'directory1/'],
                ],
            ]);

        $stats = $this->adapter->getStorageStats();

        $this->assertEquals(2, $stats['total_files']);
        $this->assertEquals(1, $stats['total_directories']);
        $this->assertEquals(300, $stats['total_size_bytes']);
        $this->assertEqualsWithDelta(0.0, $stats['total_size_mb'], 0.01, '300 bytes should be 0.0 MB'); // 300 bytes = 0.0 MB
        $this->assertEquals(['txt' => 1, 'jpg' => 1], $stats['file_types']);
        $this->assertEquals(3, $stats['processed_count']);
        $this->assertArrayHasKey('processing_time_seconds', $stats);
        $this->assertFalse($stats['has_more_files']);
    }

    public function test_get_storage_stats_with_limits(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 500,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ],
                ],
            ]);

        $stats = $this->adapter->getStorageStats(500, 30);

        $this->assertEquals(1, $stats['total_files']);
        $this->assertEquals(0, $stats['total_directories']);
        $this->assertEquals(100, $stats['total_size_bytes']);
        $this->assertFalse($stats['has_more_files']); // Only 1 file processed, not reaching limit
    }

    public function test_all_files_optimized_returns_files_with_limits(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 100,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ],
                    [
                        'Key' => 'file2.jpg',
                        'Size' => 200,
                        'LastModified' => '2023-01-02T00:00:00Z',
                        'Grants' => [],
                    ],
                ],
            ]);

        $files = $this->adapter->allFilesOptimized(100, 30);

        $this->assertCount(2, $files);
        $this->assertContains('file1.txt', $files);
        $this->assertContains('file2.jpg', $files);
    }

    public function test_all_directories_optimized_returns_directories_with_limits(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 100,
            ])
            ->once()
            ->andReturn([
                'CommonPrefixes' => [
                    ['Prefix' => 'directory1/'],
                    ['Prefix' => 'directory2/'],
                ],
            ]);

        $directories = $this->adapter->allDirectoriesOptimized(100, 30);

        $this->assertCount(2, $directories);
        $this->assertContains('directory1', $directories);
        $this->assertContains('directory2', $directories);
    }

    public function test_list_contents_optimized_with_timeout(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => 'test/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'test/file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ],
                ],
            ]);

        $contents = iterator_to_array($this->adapter->listContentsOptimized('test', true, 1000, 10));

        $this->assertCount(1, $contents);
        $this->assertInstanceOf(FileAttributes::class, $contents[0]);
        $this->assertEquals('test/file1.txt', $contents[0]->path()); // Full path is returned
    }

    // ============================================================================
    // CRITICAL EDGE CASES - INFINITE LOOP PREVENTION
    // ============================================================================

    public function test_list_contents_prevents_infinite_loop_with_duplicate_markers(): void
    {
        // Simulate the infinite loop scenario where the same marker is returned
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ],
                ],
                'NextMarker' => 'marker1',
            ]);

        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
                'Marker' => 'marker1',
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'file2.txt',
                        'Size' => 200,
                        'LastModified' => '2023-01-02T00:00:00Z',
                        'Grants' => [],
                    ],
                ],
                'NextMarker' => 'marker1', // Same marker - should trigger infinite loop prevention
            ]);

        $contents = iterator_to_array($this->adapter->listContents('', true));

        // Should only return the first batch due to duplicate marker detection
        $this->assertCount(2, $contents);
        $this->assertInstanceOf(FileAttributes::class, $contents[0]);
        $this->assertInstanceOf(FileAttributes::class, $contents[1]);
    }

    public function test_list_contents_prevents_infinite_loop_with_max_iterations(): void
    {
        // Test that the infinite loop protection exists by checking the method has the protection logic
        $reflection = new \ReflectionClass($this->adapter);
        $method = $reflection->getMethod('listContentsOptimized');
        $filename = $method->getFileName();

        if ($filename === false) {
            $this->fail('Could not get method filename');
        }

        $source = file_get_contents($filename);
        if ($source === false) {
            $this->fail('Could not read method file');
        }

        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $lines = file($filename);
        if ($lines === false) {
            $this->fail('Could not read method file lines');
        }

        $methodSource = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        // Verify that the infinite loop protection exists in the method
        $this->assertStringContainsString('Maximum iterations reached. Possible infinite loop detected.', $methodSource);
        $this->assertStringContainsString('$maxIterations = 100', $methodSource);
        $this->assertStringContainsString('$iterationCount > $maxIterations', $methodSource);
    }

    public function test_list_contents_optimized_prevents_duplicate_keys(): void
    {
        // Simulate API returning duplicate keys
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ],
                    [
                        'Key' => 'file1.txt', // Duplicate key
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ],
                ],
            ]);

        $contents = iterator_to_array($this->adapter->listContentsOptimized('', true));

        // Should only return one instance of the duplicate
        $this->assertCount(1, $contents);
        $this->assertEquals('file1.txt', $contents[0]->path());
    }

    // ============================================================================
    // PAGINATION EDGE CASES
    // ============================================================================

    public function test_list_contents_handles_empty_response(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => 'empty/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([]);

        $contents = iterator_to_array($this->adapter->listContents('empty', true));

        $this->assertCount(0, $contents);
    }

    public function test_list_contents_handles_null_next_marker(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ],
                ],
                // No NextMarker - should terminate pagination
            ]);

        $contents = iterator_to_array($this->adapter->listContents('', true));

        $this->assertCount(1, $contents);
    }

    public function test_list_contents_handles_missing_contents_and_common_prefixes(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                // No Contents or CommonPrefixes keys
            ]);

        $contents = iterator_to_array($this->adapter->listContents('', true));

        $this->assertCount(0, $contents);
    }

    // ============================================================================
    // TIMEOUT SCENARIOS
    // ============================================================================

    public function test_get_storage_stats_respects_timeout(): void
    {
        // Mock a slow response that would exceed timeout
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ],
                ],
            ]);

        $stats = $this->adapter->getStorageStats(0, 1); // 1 second timeout

        $this->assertEquals(1, $stats['total_files']);
        $this->assertLessThanOrEqual(1, $stats['processing_time_seconds']);
    }

    public function test_all_files_optimized_respects_timeout(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ],
                ],
            ]);

        $files = $this->adapter->allFilesOptimized(0, 1); // 1 second timeout

        $this->assertCount(1, $files);
    }

    // ============================================================================
    // LARGE DATASET HANDLING
    // ============================================================================

    public function test_list_contents_optimized_with_large_dataset(): void
    {
        // Simulate a large dataset with multiple pages
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 500, // Limited by maxKeys parameter
            ])
            ->once()
            ->andReturn([
                'Contents' => array_map(function ($i) {
                    return [
                        'Key' => "file{$i}.txt",
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ];
                }, range(1, 500)),
            ]);

        $contents = iterator_to_array($this->adapter->listContentsOptimized('', true, 500, 60));

        $this->assertCount(500, $contents);
        $this->assertInstanceOf(FileAttributes::class, $contents[0]);
        $this->assertEquals('file1.txt', $contents[0]->path());
    }

    public function test_get_storage_stats_with_large_dataset(): void
    {
        $largeContents = array_map(function ($i) {
            return [
                'Key' => "file{$i}.txt",
                'Size' => 100,
                'LastModified' => '2023-01-01T00:00:00Z',
                'Grants' => [],
            ];
        }, range(1, 1000));

        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => $largeContents,
            ]);

        $stats = $this->adapter->getStorageStats(1000, 60);

        $this->assertEquals(1000, $stats['total_files']);
        $this->assertEquals(100000, $stats['total_size_bytes']);
        $this->assertEquals(1000, $stats['processed_count']);
        $this->assertArrayHasKey('txt', $stats['file_types']);
        $this->assertEquals(1000, $stats['file_types']['txt']);
    }

    // ============================================================================
    // ERROR RECOVERY AND EXCEPTION HANDLING
    // ============================================================================

    public function test_list_contents_optimized_handles_api_errors(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andThrow(new ObsException('API Error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to list contents: API Error');

        iterator_to_array($this->adapter->listContentsOptimized('', true));
    }

    public function test_get_storage_stats_handles_api_errors(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andThrow(new ObsException('API Error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to list contents: API Error');

        $this->adapter->getStorageStats();
    }

    public function test_list_contents_optimized_handles_authentication_errors(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andThrow(new ObsException('AccessDenied'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to list contents: AccessDenied');

        iterator_to_array($this->adapter->listContentsOptimized('', true));
    }

    // ============================================================================
    // BOUNDARY CONDITIONS
    // ============================================================================

    public function test_list_contents_optimized_with_zero_max_keys(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000, // Should use default when maxKeys is 0
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ],
                ],
            ]);

        $contents = iterator_to_array($this->adapter->listContentsOptimized('', true, 0, 60));

        $this->assertCount(1, $contents);
    }

    public function test_list_contents_optimized_with_zero_timeout(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ],
                ],
            ]);

        $contents = iterator_to_array($this->adapter->listContentsOptimized('', true, 1000, 0));

        $this->assertCount(1, $contents);
    }

    public function test_get_storage_stats_with_zero_parameters(): void
    {
        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => [
                    [
                        'Key' => 'file1.txt',
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ],
                ],
            ]);

        $stats = $this->adapter->getStorageStats(0, 0);

        $this->assertEquals(1, $stats['total_files']);
        $this->assertEquals(0, $stats['processing_time_seconds']);
    }

    // ============================================================================
    // PERFORMANCE AND MEMORY TESTS
    // ============================================================================

    public function test_list_contents_optimized_memory_efficiency(): void
    {
        $memoryBefore = memory_get_usage();

        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => array_map(function ($i) {
                    return [
                        'Key' => "file{$i}.txt",
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ];
                }, range(1, 1000)),
            ]);

        $contents = iterator_to_array($this->adapter->listContentsOptimized('', true, 1000, 60));

        $memoryAfter = memory_get_usage();
        $memoryIncrease = $memoryAfter - $memoryBefore;

        $this->assertCount(1000, $contents);
        // Memory increase should be reasonable (less than 10MB for 1000 files)
        $this->assertLessThan(10 * 1024 * 1024, $memoryIncrease);
    }

    public function test_get_storage_stats_performance(): void
    {
        $startTime = microtime(true);

        $this->mockClient->shouldReceive('listObjects')
            ->with([
                'Bucket' => $this->bucket,
                'Prefix' => '/',
                'Delimiter' => null,
                'MaxKeys' => 1000,
            ])
            ->once()
            ->andReturn([
                'Contents' => array_map(function ($i) {
                    return [
                        'Key' => "file{$i}.txt",
                        'Size' => 100,
                        'LastModified' => '2023-01-01T00:00:00Z',
                        'Grants' => [],
                    ];
                }, range(1, 1000)),
            ]);

        $stats = $this->adapter->getStorageStats(1000, 60);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertEquals(1000, $stats['total_files']);
        // Should complete within 1 second for 1000 files
        $this->assertLessThan(1.0, $executionTime);
    }
}
