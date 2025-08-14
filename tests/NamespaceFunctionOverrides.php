<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs;

use LaravelFlysystemHuaweiObs\Tests\FunctionOverrideControl as FOC;

function class_exists(string $class, bool $autoload = true): bool
{
    if (FOC::$enabled) {
        if ($class === 'GuzzleHttp\\Client') {
            return FOC::$guzzleClientExists;
        }
        if ($class === 'Composer\\InstalledVersions') {
            return FOC::$composerInstalledVersionsExists;
        }
    }

    return \class_exists($class, $autoload);
}

function interface_exists(string $interface, bool $autoload = true): bool
{
    if (FOC::$enabled) {
        if ($interface === 'Psr\\Http\\Client\\ClientInterface') {
            return FOC::$psr18InterfaceExists;
        }
    }

    return \interface_exists($interface, $autoload);
}
