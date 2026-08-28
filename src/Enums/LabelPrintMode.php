<?php

namespace SmartDato\CorreosShipping\Enums;

use SmartDato\CorreosShipping\Enums\Concerns\HasOptions;

enum LabelPrintMode: int
{
    use HasOptions;

    case A4 = 1;
    case Labeler = 2;

    public function label(): string
    {
        return match ($this) {
            self::A4 => 'A4 sheet',
            self::Labeler => 'Labeler',
        };
    }
}
