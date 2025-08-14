<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests\Support;

interface HasHuaweiObsMacros
{
    /**
     * @param  array<string,string>  $headers
     */
    public function createSignedUrl(string $path, string $method = 'GET', int $expires = 3600, array $headers = []): string;

    /**
     * @param  array<int, array<string,mixed>>  $conditions
     * @return array<string,mixed>
     */
    public function createPostSignature(string $path, array $conditions = [], int $expires = 3600): array;

    /**
     * @param  array<string,string>  $tags
     */
    public function setObjectTags(string $path, array $tags): void;

    /**
     * @return array<string,string>
     */
    public function getObjectTags(string $path): array;

    public function deleteObjectTags(string $path): void;

    public function restoreObject(string $path, int $days = 1): void;

    /**
     * @return array<string,mixed>
     */
    public function getStorageStats(int $maxFiles = 0, int $timeout = 60): array;

    /**
     * @return array<int,string>
     */
    public function allFilesOptimized(int $maxKeys = 0, int $timeout = 60): array;

    /**
     * @return array<int,string>
     */
    public function allDirectoriesOptimized(int $maxKeys = 0, int $timeout = 60): array;

    /**
     * @return iterable<mixed>
     */
    public function listContentsOptimized(string $path = '', bool $deep = false, int $maxKeys = 0, int $timeout = 60): iterable;
}
