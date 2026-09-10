<?php

namespace Arzcode\LaravelCorreos\Data\Tracking;

use Spatie\LaravelData\Data;

class ExpeditionPackageData extends Data
{
    public function __construct(
        public ?string $shippingCode,
        public ?string $number,
    ) {}
}
