<?php

namespace SmartDato\CorreosShipping\Enums;

use SmartDato\CorreosShipping\Enums\Concerns\HasOptions;

enum LabelFormat: int
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
