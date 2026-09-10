<?php

namespace Arzcode\LaravelCorreos\Requests\Preregister;

use Arzcode\LaravelCorreos\Data\Preregister\AnnulmentExpeditionRequestData;
use Arzcode\LaravelCorreos\Data\Preregister\AnnulmentResponseData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class CancelExpeditionRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected AnnulmentExpeditionRequestData $data,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/delivery/annulment/expedition';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }

    public function createDtoFromResponse(Response $response): AnnulmentResponseData
    {
        return AnnulmentResponseData::from($response->json());
    }
}
