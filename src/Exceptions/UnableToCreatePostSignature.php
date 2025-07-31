<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Exceptions;

/**
 * Exception thrown when unable to create a post signature.
 */
class UnableToCreatePostSignature extends HuaweiObsException
{
    public static function forLocation(string $location, ?\Throwable $previous = null): self
    {
        return new self("Unable to create post signature for location: {$location}", 0, $previous);
    }
}
