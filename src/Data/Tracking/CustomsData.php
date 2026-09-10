<?php

namespace Arzcode\LaravelCorreos\Data\Tracking;

use Spatie\LaravelData\Data;

class CustomsData extends Data
{
    public function __construct(
        public ?string $color,
        public ?string $description,
    ) {}
}
