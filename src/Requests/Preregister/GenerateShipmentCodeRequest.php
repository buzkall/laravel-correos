<?php

namespace Arzcode\LaravelCorreos\Requests\Preregister;

use Arzcode\LaravelCorreos\Data\Preregister\GenerateExpeditionResponseData;
use Arzcode\LaravelCorreos\Data\Preregister\GenerateShipmentCodeRequestData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class GenerateShipmentCodeRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected GenerateShipmentCodeRequestData $data,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/generate/shipmentcode';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }

    public function createDtoFromResponse(Response $response): GenerateExpeditionResponseData
    {
        return GenerateExpeditionResponseData::from($response->json());
    }
}
