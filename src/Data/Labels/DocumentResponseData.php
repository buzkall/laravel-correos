<?php

namespace SmartDato\CorreosShipping\Data\Labels;

use Spatie\LaravelData\Data;

class DocumentResponseData extends Data
{
    public function __construct(
        public ?string $pdf,
        public ?string $error,
    ) {}

    /**
     * The PDF as raw bytes, ready to stream as a download. Null when the
     * response carries no PDF, or carries one that is not valid base64.
     */
    public function decodedPdf(): ?string
    {
        if ($this->pdf === null || $this->pdf === '') {
            return null;
        }

        return base64_decode($this->pdf, true) ?: null;
    }
}
