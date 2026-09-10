<?php

namespace Arzcode\LaravelCorreos\Data\Tracking;

use Spatie\LaravelData\Data;

class TrackingErrorData extends Data
{
    public function __construct(
        public ?string $codError,
        public ?string $desError,
    ) {}
}
