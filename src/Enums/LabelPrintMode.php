<?php

namespace SmartDato\CorreosShipping\Enums;

use SmartDato\CorreosShipping\Enums\Concerns\HasOptions;
use SmartDato\CorreosShipping\Enums\Contracts\Optionable;

enum LabelPrintMode: int implements Optionable
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
