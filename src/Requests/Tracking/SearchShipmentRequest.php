<?php

namespace Arzcode\LaravelCorreos\Requests\Tracking;

use Arzcode\LaravelCorreos\Data\Tracking\ShipmentSearchResponseData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class SearchShipmentRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $shippingCode,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/search/'.$this->shippingCode;
    }

    public function createDtoFromResponse(Response $response): ShipmentSearchResponseData
    {
        return ShipmentSearchResponseData::from($response->json());
    }
}
