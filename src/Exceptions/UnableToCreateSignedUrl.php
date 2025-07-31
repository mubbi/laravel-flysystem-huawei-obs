<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Exceptions;

/**
 * Exception thrown when unable to create a signed URL.
 */
class UnableToCreateSignedUrl extends HuaweiObsException
{
    public static function forLocation(string $location, ?\Throwable $previous = null): self
    {
        return new self("Unable to create signed URL for location: {$location}", 0, $previous);
    }
}
