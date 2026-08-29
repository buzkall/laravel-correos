<?php

namespace SmartDato\CorreosShipping\Enums;

use SmartDato\CorreosShipping\Enums\Concerns\HasOptions;
use SmartDato\CorreosShipping\Enums\Contracts\Optionable;

enum AdmissionMethod: int implements Optionable
{
    use HasOptions;

    case Office = 1;
    case Citypaq = 2;
    case DeliveryUnit = 3;

    public function label(): string
    {
        return match ($this) {
            self::Office => 'Post office',
            self::Citypaq => 'Citypaq',
            self::DeliveryUnit => 'Delivery unit',
        };
    }
}
