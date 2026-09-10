<?php

namespace Arzcode\LaravelCorreos\Enums;

use Arzcode\LaravelCorreos\Enums\Concerns\HasOptions;
use Arzcode\LaravelCorreos\Enums\Contracts\Optionable;

enum LabelFormat: int implements Optionable
{
    use HasOptions;

    case XML = 1;
    case PDF = 2;
    case ZPL = 3;

    public function label(): string
    {
        return match ($this) {
            self::XML => 'XML',
            self::PDF => 'PDF',
            self::ZPL => 'ZPL',
        };
    }
}
