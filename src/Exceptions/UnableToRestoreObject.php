<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Exceptions;

/**
 * Exception thrown when unable to restore an object.
 */
class UnableToRestoreObject extends HuaweiObsException
{
    public static function forLocation(string $location, ?\Throwable $previous = null): self
    {
        return new self("Unable to restore object for location: {$location}", 0, $previous);
    }
}
