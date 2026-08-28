<?php

declare(strict_types=1);

namespace SmartDato\CorreosShipping\Data\Preregister;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class QueryResponseData extends Data
{
    /**
     * @param  array<QueryShipmentResponseData>|null  $shipments
     */
    public function __construct(
        #[DataCollectionOf(QueryShipmentResponseData::class)]
        public ?array $shipments,
        public ?string $error,
    ) {}
}
