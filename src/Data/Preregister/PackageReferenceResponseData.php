<?php

namespace Arzcode\LaravelCorreos\Data\Preregister;

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
