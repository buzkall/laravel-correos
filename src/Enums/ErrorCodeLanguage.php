<?php

namespace SmartDato\CorreosShipping\Enums;

use SmartDato\CorreosShipping\Enums\Concerns\HasOptions;

enum ErrorCodeLanguage: string
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
