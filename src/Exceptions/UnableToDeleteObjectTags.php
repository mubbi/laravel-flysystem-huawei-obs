<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Exceptions;

/**
 * Exception thrown when unable to delete object tags.
 */
class UnableToDeleteObjectTags extends HuaweiObsException
{
    public static function forLocation(string $location, ?\Throwable $previous = null): self
    {
        return new self("Unable to delete object tags for location: {$location}", 0, $previous);
    }
}
