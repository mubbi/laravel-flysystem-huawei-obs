<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests\Adapter;

use LaravelFlysystemHuaweiObs\AbstractHuaweiObsAdapter;

class ConcreteTestHuaweiObsAdapter extends AbstractHuaweiObsAdapter
{
    public function callVisibilityToAcl(string $visibility): string
    {
        return $this->visibilityToAcl($visibility);
    }

    /**
     * @param  array<int, array<string, mixed>>  $grants
     */
    public function callAclToVisibility(array $grants): string
    {
        return $this->aclToVisibility($grants);
    }

    public function callGetKey(string $path): string
    {
        return $this->getKey($path);
    }

    public function callGetRelativePath(string $key): string
    {
        return $this->getRelativePath($key);
    }

    public function callExtractErrorCode(\Obs\ObsException $e): ?string
    {
        return $this->extractErrorCode($e);
    }

    public function callIsNotFoundError(\Obs\ObsException $e): bool
    {
        return $this->isNotFoundError($e);
    }

    public function callIsAuthError(\Obs\ObsException $e): bool
    {
        return $this->isAuthenticationError($e);
    }

    public function callIsBucketError(\Obs\ObsException $e): bool
    {
        return $this->isBucketError($e);
    }
}
