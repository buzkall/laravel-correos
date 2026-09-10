<?php

namespace Arzcode\LaravelCorreos\Requests\Preregister;

use Arzcode\LaravelCorreos\Data\Preregister\BackofficeResponseData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetBackofficeShipmentRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $shipmentCode,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/delivery/package/backoffice/shipment/'.rawurlencode($this->shipmentCode);
    }

    public function createDtoFromResponse(Response $response): BackofficeResponseData
    {
        return BackofficeResponseData::from($response->json());
    }
}
