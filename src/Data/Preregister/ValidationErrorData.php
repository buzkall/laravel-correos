<?php

namespace Arzcode\LaravelCorreos\Data\Preregister;

use Spatie\LaravelData\Data;

class ValidationErrorData extends Data
{
    public function __construct(
        public ?int $errorCode,
        public ?string $description,
        public ?string $errorFieldName,
    ) {}
}
