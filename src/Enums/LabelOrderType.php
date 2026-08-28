<?php

namespace SmartDato\CorreosShipping\Enums;

use SmartDato\CorreosShipping\Enums\Concerns\HasOptions;

enum LabelOrderType: int
{
    use HasOptions;

    case InternationalPoBox = 1;
    case Company = 2;
    case LastName = 3;
    case PackageId = 4;
    case ClientReference = 5;

    public function label(): string
    {
        return match ($this) {
            self::InternationalPoBox => 'International PO box',
            self::Company => 'Company',
            self::LastName => 'Last name',
            self::PackageId => 'Package ID',
            self::ClientReference => 'Client reference',
        };
    }
}
