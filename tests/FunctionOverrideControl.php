<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs\Tests;

final class FunctionOverrideControl
{
    public static bool $enabled = false;

    public static bool $composerInstalledVersionsExists = false;

    public static bool $guzzleClientExists = true;

    public static bool $psr18InterfaceExists = false;

    public static function reset(): void
    {
        self::$enabled = false;
        self::$composerInstalledVersionsExists = false;
        self::$guzzleClientExists = true;
        self::$psr18InterfaceExists = false;
    }
}
