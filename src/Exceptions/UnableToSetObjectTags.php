<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Exceptions;

/**
 * Exception thrown when unable to set object tags.
 */
class UnableToSetObjectTags extends HuaweiObsException
{
    public static function forLocation(string $location, ?\Throwable $previous = null): self
    {
        return new self("Unable to set object tags for location: {$location}", 0, $previous);
    }
}
