<?php

namespace SmartDato\CorreosShipping\Enums;

use SmartDato\CorreosShipping\Enums\Concerns\HasOptions;

enum DoiType: string
{
    use HasOptions;

    case European = '0';
    case DNI = '1';
    case NIE = '3';
    case Other = '4';
    case CIF = '10';

    public function label(): string
    {
        return match ($this) {
            self::European => 'European ID',
            self::DNI => 'DNI',
            self::NIE => 'NIE',
            self::Other => 'Other document',
            self::CIF => 'CIF',
        };
    }
}
