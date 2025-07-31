<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Exceptions;

/**
 * Exception thrown when unable to get object tags.
 */
class UnableToGetObjectTags extends HuaweiObsException
{
    public static function forLocation(string $location, ?\Throwable $previous = null): self
    {
        return new self("Unable to get object tags for location: {$location}", 0, $previous);
    }
}
