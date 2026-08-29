<?php

namespace SmartDato\CorreosShipping\Enums;

use SmartDato\CorreosShipping\Enums\Concerns\HasOptions;
use SmartDato\CorreosShipping\Enums\Contracts\Optionable;

enum DocumentationType: int implements Optionable
{
    use HasOptions;

    case All = 0;
    case Label = 1;
    case CN22_CN23 = 2;
    case DCAF = 5;
    case DDP = 6;

    public function label(): string
    {
        return match ($this) {
            self::All => 'All documents',
            self::Label => 'Label',
            self::CN22_CN23 => 'CN22/CN23 customs form',
            self::DCAF => 'DCAF',
            self::DDP => 'DDP',
        };
    }
}
