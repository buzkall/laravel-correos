<?php

namespace Arzcode\LaravelCorreos\Requests\Preregister;

use Arzcode\LaravelCorreos\Data\Preregister\DeliveryRequestData;
use Arzcode\LaravelCorreos\Data\Preregister\ModifyResponseData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class ModifyShipmentRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        protected DeliveryRequestData $data,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/delivery/package';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }

    public function createDtoFromResponse(Response $response): ModifyResponseData
    {
        return ModifyResponseData::from($response->json());
    }
}
