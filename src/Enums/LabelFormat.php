<?php

namespace SmartDato\CorreosShipping\Enums;

use SmartDato\CorreosShipping\Enums\Concerns\HasOptions;
use SmartDato\CorreosShipping\Enums\Contracts\Optionable;

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
