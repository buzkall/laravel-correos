<?php

namespace Arzcode\LaravelCorreos\Requests\Preregister;

use Arzcode\LaravelCorreos\Data\Preregister\CnDeliveryResponseData;
use Arzcode\LaravelCorreos\Data\Preregister\DeliveryRequestData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class CreateCnShipmentsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected DeliveryRequestData $data,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/delivery/cn';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }

    public function createDtoFromResponse(Response $response): CnDeliveryResponseData
    {
        return CnDeliveryResponseData::from($response->json());
    }
}
