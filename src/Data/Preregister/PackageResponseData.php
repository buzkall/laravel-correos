<?php

declare(strict_types=1);

namespace SmartDato\CorreosShipping\Data\Preregister;

use Spatie\LaravelData\Data;

class PackageResponseData extends Data
{
    public function __construct(
        public ?string $packageId,
        public ?string $packageCode,
    ) {}
}
