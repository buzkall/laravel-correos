<?php

namespace SmartDato\CorreosShipping\Enums;

use SmartDato\CorreosShipping\Enums\Concerns\HasOptions;
use SmartDato\CorreosShipping\Enums\Contracts\Optionable;

enum ErrorCodeLanguage: string implements Optionable
{
    use HasOptions;

    case Spanish = 'spa';
    case English = 'eng';

    public function label(): string
    {
        return match ($this) {
            self::Spanish => 'Spanish',
            self::English => 'English',
        };
    }
}
