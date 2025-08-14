<?php

declare(strict_types=1);

/**
 * Laravel Flysystem Huawei OBS Service Provider
 *
 * Service provider for Laravel Flysystem Huawei OBS adapter.
 * Registers the huawei-obs disk driver with Laravel's filesystem.
 *
 * @author  Mubbasher Ahmed <hello@mubbi.me>
 *
 * @link    https://mubbi.me
 *
 * @license MIT
 */

namespace LaravelFlysystemHuaweiObs;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

class HuaweiObsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Publish configuration file
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/huawei-obs.php' => config_path('huawei-obs.php'),
            ], 'huawei-obs-config');

            // Register Artisan commands
            $this->commands([
                \LaravelFlysystemHuaweiObs\Console\TestHuaweiObsCommand::class,
            ]);
        }

        // Register Laravel Storage macros for advanced features
        if (method_exists(FilesystemAdapter::class, 'macro')) {
            FilesystemAdapter::macro('createSignedUrl', function (string $path, string $method = 'GET', int $expires = 3600, array $headers = []): string {
                $adapter = $this->getAdapter();
                if ($adapter instanceof \LaravelFlysystemHuaweiObs\LaravelHuaweiObsAdapter || $adapter instanceof \LaravelFlysystemHuaweiObs\HuaweiObsAdapter) {
                    return $adapter->createSignedUrl($path, $method, $expires, $headers);
                }

                throw new \BadMethodCallException('createSignedUrl not supported by this adapter');
            });

            FilesystemAdapter::macro('createPostSignature', function (string $path, array $conditions = [], int $expires = 3600): array {
                $adapter = $this->getAdapter();
                if ($adapter instanceof \LaravelFlysystemHuaweiObs\LaravelHuaweiObsAdapter || $adapter instanceof \LaravelFlysystemHuaweiObs\HuaweiObsAdapter) {
                    return $adapter->createPostSignature($path, $conditions, $expires);
                }

                throw new \BadMethodCallException('createPostSignature not supported by this adapter');
            });

            FilesystemAdapter::macro('setObjectTags', function (string $path, array $tags): void {
                $adapter = $this->getAdapter();
                if ($adapter instanceof \LaravelFlysystemHuaweiObs\LaravelHuaweiObsAdapter || $adapter instanceof \LaravelFlysystemHuaweiObs\HuaweiObsAdapter) {
                    $adapter->setObjectTags($path, $tags);

                    return;
                }

                throw new \BadMethodCallException('setObjectTags not supported by this adapter');
            });

            FilesystemAdapter::macro('getObjectTags', function (string $path): array {
                $adapter = $this->getAdapter();
                if ($adapter instanceof \LaravelFlysystemHuaweiObs\LaravelHuaweiObsAdapter || $adapter instanceof \LaravelFlysystemHuaweiObs\HuaweiObsAdapter) {
                    return $adapter->getObjectTags($path);
                }

                throw new \BadMethodCallException('getObjectTags not supported by this adapter');
            });

            FilesystemAdapter::macro('deleteObjectTags', function (string $path): void {
                $adapter = $this->getAdapter();
                if ($adapter instanceof \LaravelFlysystemHuaweiObs\LaravelHuaweiObsAdapter || $adapter instanceof \LaravelFlysystemHuaweiObs\HuaweiObsAdapter) {
                    $adapter->deleteObjectTags($path);

                    return;
                }

                throw new \BadMethodCallException('deleteObjectTags not supported by this adapter');
            });

            FilesystemAdapter::macro('restoreObject', function (string $path, int $days = 1): void {
                $adapter = $this->getAdapter();
                if ($adapter instanceof \LaravelFlysystemHuaweiObs\LaravelHuaweiObsAdapter || $adapter instanceof \LaravelFlysystemHuaweiObs\HuaweiObsAdapter) {
                    $adapter->restoreObject($path, $days);

                    return;
                }

                throw new \BadMethodCallException('restoreObject not supported by this adapter');
            });

            FilesystemAdapter::macro('temporaryUploadUrl', function (string $path, \DateTimeInterface $expiration, array $options = []): string {
                $adapter = $this->getAdapter();
                if ($adapter instanceof \LaravelFlysystemHuaweiObs\LaravelHuaweiObsAdapter || $adapter instanceof \LaravelFlysystemHuaweiObs\HuaweiObsAdapter) {
                    return $adapter->temporaryUploadUrl($path, $expiration, $options);
                }

                throw new \BadMethodCallException('temporaryUploadUrl not supported by this adapter');
            });

            FilesystemAdapter::macro('getStorageStats', function (int $maxFiles = 0, int $timeout = 60): array {
                $adapter = $this->getAdapter();
                if ($adapter instanceof \LaravelFlysystemHuaweiObs\LaravelHuaweiObsAdapter || $adapter instanceof \LaravelFlysystemHuaweiObs\HuaweiObsAdapter) {
                    return $adapter->getStorageStats($maxFiles, $timeout);
                }

                throw new \BadMethodCallException('getStorageStats not supported by this adapter');
            });

            FilesystemAdapter::macro('allFilesOptimized', function (int $maxKeys = 0, int $timeout = 60): array {
                $adapter = $this->getAdapter();
                if ($adapter instanceof \LaravelFlysystemHuaweiObs\LaravelHuaweiObsAdapter || $adapter instanceof \LaravelFlysystemHuaweiObs\HuaweiObsAdapter) {
                    return $adapter->allFilesOptimized($maxKeys, $timeout);
                }

                throw new \BadMethodCallException('allFilesOptimized not supported by this adapter');
            });

            FilesystemAdapter::macro('allDirectoriesOptimized', function (int $maxKeys = 0, int $timeout = 60): array {
                $adapter = $this->getAdapter();
                if ($adapter instanceof \LaravelFlysystemHuaweiObs\LaravelHuaweiObsAdapter || $adapter instanceof \LaravelFlysystemHuaweiObs\HuaweiObsAdapter) {
                    return $adapter->allDirectoriesOptimized($maxKeys, $timeout);
                }

                throw new \BadMethodCallException('allDirectoriesOptimized not supported by this adapter');
            });

            FilesystemAdapter::macro('listContentsOptimized', function (string $path, bool $deep, int $maxKeys = 0, int $timeout = 60): iterable {
                $adapter = $this->getAdapter();
                if ($adapter instanceof \LaravelFlysystemHuaweiObs\LaravelHuaweiObsAdapter || $adapter instanceof \LaravelFlysystemHuaweiObs\HuaweiObsAdapter) {
                    return $adapter->listContentsOptimized($path, $deep, $maxKeys, $timeout);
                }

                throw new \BadMethodCallException('listContentsOptimized not supported by this adapter');
            });
        }

        Storage::extend('huawei-obs', function ($app, $config) {
            // Validate required configuration
            $required = ['key', 'secret', 'bucket', 'endpoint'];
            foreach ($required as $field) {
                if (empty($config[$field])) {
                    throw new \InvalidArgumentException("Missing required configuration for huawei-obs disk: {$field}");
                }
            }

            // Validate endpoint format
            if (! filter_var($config['endpoint'], FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException("Invalid endpoint URL for huawei-obs disk: {$config['endpoint']}");
            }

            $adapter = new LaravelHuaweiObsAdapter(
                $config['key'],
                $config['secret'],
                $config['bucket'],
                $config['endpoint'],
                $config['prefix'] ?? null,
                HttpClientFactory::create($config['http_client'] ?? []),
                $config['security_token'] ?? null,
                $config['retry_attempts'] ?? 3,
                $config['retry_delay'] ?? 1,
                $config['logging_enabled'] ?? false,
                $config['log_operations'] ?? false,
                $config['log_errors'] ?? true,
                $config['http_client']['verify'] ?? true,
                $config['signature'] ?? null,
                $config['path_style'] ?? null,
                $config['region'] ?? null,
                $config['ssl.certificate_authority'] ?? null,
                $config['max_retry_count'] ?? null,
                $config['timeout'] ?? null,
                $config['socket_timeout'] ?? null,
                $config['connect_timeout'] ?? null,
                $config['chunk_size'] ?? null,
                $config['exception_response_mode'] ?? null,
                $config['is_cname'] ?? null
            );

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }
}
