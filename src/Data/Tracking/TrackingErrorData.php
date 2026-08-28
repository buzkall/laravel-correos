<?php

declare(strict_types=1);

namespace SmartDato\CorreosShipping\Data\Tracking;

use Spatie\LaravelData\Data;

class TrackingErrorData extends Data
{
    public function __construct(
        public ?string $codError,
        public ?string $desError,
    ) {}
}
