<?php

declare(strict_types=1);

namespace SmartDato\CorreosShipping\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SmartDato\CorreosShipping\CorreosShipping
 */
class CorreosShipping extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SmartDato\CorreosShipping\CorreosShipping::class;
    }
}
