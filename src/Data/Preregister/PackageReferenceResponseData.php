<?php

declare(strict_types=1);

namespace SmartDato\CorreosShipping\Data\Preregister;

use Spatie\LaravelData\Data;

class PackageReferenceResponseData extends Data
{
    /**
     * @param  array<string>|null  $packageCodes
     */
    public function __construct(
        public ?array $packageCodes,
    ) {}
}
