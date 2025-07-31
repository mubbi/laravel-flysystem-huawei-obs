<?php

declare(strict_types=1);

/**
 * Base exception for Huawei OBS adapter.
 *
 * @author  Mubbasher Ahmed <hello@mubbi.me>
 *
 * @link    https://mubbi.me
 *
 * @license MIT
 */

namespace LaravelFlysystemHuaweiObs\Exceptions;

use League\Flysystem\FilesystemException;

class HuaweiObsException extends \RuntimeException implements FilesystemException
{
    //
}
