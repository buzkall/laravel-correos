<?php

namespace Arzcode\LaravelCorreos\Enums;

use Arzcode\LaravelCorreos\Enums\Concerns\HasOptions;
use Arzcode\LaravelCorreos\Enums\Contracts\Optionable;

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
