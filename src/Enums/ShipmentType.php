<?php

namespace SmartDato\CorreosShipping\Enums;

use SmartDato\CorreosShipping\Enums\Concerns\HasOptions;

enum ShipmentType: string
{
    use HasOptions;

    case Documents = '1';
    case Goods = '2';
    case Gift = '3';
    case Samples = '4';
    case Returns = '5';
    case Other = '6';
    case Dangerous = '7';

    public function label(): string
    {
        return match ($this) {
            self::Documents => 'Documents',
            self::Goods => 'Goods',
            self::Gift => 'Gift',
            self::Samples => 'Commercial samples',
            self::Returns => 'Returned goods',
            self::Other => 'Other',
            self::Dangerous => 'Dangerous goods',
        };
    }
}
