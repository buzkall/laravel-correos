<?php

namespace Arzcode\LaravelCorreos\Requests\Labels;

use Arzcode\LaravelCorreos\Data\Labels\DocumentBackofficeResponseData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetDocumentBackofficeRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $shipment,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/documents/backoffice/'.$this->shipment;
    }

    public function createDtoFromResponse(Response $response): DocumentBackofficeResponseData
    {
        return DocumentBackofficeResponseData::from($response->json());
    }
}
