<?php

declare(strict_types=1);

/**
 * HTTP Client Factory
 *
 * Factory class to create HTTP clients compatible with different Guzzle versions.
 *
 * @author  Mubbasher Ahmed <hello@mubbi.me>
 *
 * @link    https://mubbi.me
 *
 * @license MIT
 */

namespace LaravelFlysystemHuaweiObs;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

class HttpClientFactory
{
    /**
     * Create an HTTP client compatible with the installed Guzzle version
     *
     * @param  array<string, mixed>|null  $config  HTTP client configuration
     * @return ClientInterface|null The HTTP client or null if not configured
     */
    public static function create(?array $config): ?ClientInterface
    {
        if (empty($config)) {
            return null;
        }

        $clientConfig = [
            'timeout' => $config['timeout'] ?? 30,
            'connect_timeout' => $config['connect_timeout'] ?? 10,
            'verify' => $config['verify'] ?? true,
        ];

        // Add proxy if configured
        if (isset($config['proxy']) && $config['proxy'] !== null) {
            $clientConfig['proxy'] = $config['proxy'];
        }

        // Add headers if configured
        if (isset($config['headers']) && is_array($config['headers'])) {
            $clientConfig['headers'] = $config['headers'];
        }

        // Add Guzzle version specific configurations
        $guzzleVersion = self::detectGuzzleVersion();

        if ($guzzleVersion === 'v6') {
            // Guzzle v6 specific configurations
            if (isset($config['http_errors'])) {
                $clientConfig['http_errors'] = $config['http_errors'];
            }

            if (isset($config['allow_redirects'])) {
                $clientConfig['allow_redirects'] = $config['allow_redirects'];
            }
        } elseif ($guzzleVersion === 'v7') {
            // Guzzle v7 specific configurations
            if (isset($config['http_errors'])) {
                $clientConfig['http_errors'] = $config['http_errors'];
            }

            if (isset($config['allow_redirects'])) {
                $clientConfig['allow_redirects'] = $config['allow_redirects'];
            }
        } else {
            // Guzzle v8+ specific configurations
            if (isset($config['http_errors'])) {
                $clientConfig['http_errors'] = $config['http_errors'];
            }

            if (isset($config['allow_redirects'])) {
                $clientConfig['allow_redirects'] = $config['allow_redirects'];
            }
        }

        return new Client($clientConfig);
    }

    /**
     * Detect the installed Guzzle version
     *
     * @return string 'v6', 'v7', or 'v8'
     */
    public static function detectGuzzleVersion(): string
    {
        // Check if GuzzleHttp\Client exists
        if (! class_exists('GuzzleHttp\Client')) {
            return 'v7'; // Default fallback
        }

        // Try to get version from Composer's installed packages
        if (class_exists('Composer\InstalledVersions')) {
            try {
                $version = \Composer\InstalledVersions::getVersion('guzzlehttp/guzzle');
                if ($version !== null) {
                    $majorVersion = (int) explode('.', $version)[0];
                    if ($majorVersion === 6) {
                        return 'v6';
                    } elseif ($majorVersion === 7) {
                        return 'v7';
                    } elseif ($majorVersion === 8) {
                        return 'v8';
                    }
                }
            } catch (\Throwable $e) {
                // Fallback to reflection-based detection
            }
        }

        // Fallback: check class methods to determine version
        $reflection = new \ReflectionClass(Client::class);

        // Check for v6 specific methods
        if ($reflection->hasMethod('getDefaultOption')) {
            return 'v6';
        }

        // Check for v7+ specific methods
        if ($reflection->hasMethod('getConfig')) {
            // Try to distinguish between v7 and v8 by checking for v8-specific features
            // Guzzle v8 has PSR-18 compliance and some additional methods
            if (interface_exists('Psr\Http\Client\ClientInterface') && 
                $reflection->implementsInterface('Psr\Http\Client\ClientInterface')) {
                return 'v8';
            }
            
            // Check for v8-specific methods or properties
            if ($reflection->hasMethod('sendRequest') && 
                method_exists(Client::class, 'sendRequest')) {
                // Additional check for v8-specific behavior
                $sendRequestMethod = $reflection->getMethod('sendRequest');
                $parameters = $sendRequestMethod->getParameters();
                if (count($parameters) === 1 && 
                    $parameters[0]->getType() && 
                    $parameters[0]->getType()->getName() === 'Psr\Http\Message\RequestInterface') {
                    return 'v8';
                }
            }
            
            return 'v7';
        }

        return 'v7'; // Default fallback
    }
}
