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

use GuzzleHttp\Client;
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

            $adapter = new HuaweiObsAdapter(
                $config['key'],
                $config['secret'],
                $config['bucket'],
                $config['endpoint'],
                $config['prefix'] ?? null,
                $this->createHttpClient($config),
                $config['security_token'] ?? null,
                $config['retry_attempts'] ?? 3,
                $config['retry_delay'] ?? 1,
                $config['logging_enabled'] ?? false,
                $config['log_operations'] ?? false,
                $config['log_errors'] ?? true
            );

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function createHttpClient(array $config): ?Client
    {
        if (! isset($config['http_client'])) {
            return null;
        }

        $httpConfig = $config['http_client'];

        return new Client([
            'timeout' => $httpConfig['timeout'] ?? 30,
            'connect_timeout' => $httpConfig['connect_timeout'] ?? 10,
            'verify' => $httpConfig['verify'] ?? true,
            'proxy' => $httpConfig['proxy'] ?? null,
            'headers' => $httpConfig['headers'] ?? [],
        ]);
    }
}
