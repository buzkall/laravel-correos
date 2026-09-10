<?php

namespace Arzcode\LaravelCorreos\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Arzcode\LaravelCorreos\Correos
 */
class Correos extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Arzcode\LaravelCorreos\Correos::class;
    }
}
