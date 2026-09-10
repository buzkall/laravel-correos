<?php

namespace Arzcode\LaravelCorreos\Requests\Labels;

use Arzcode\LaravelCorreos\Data\Labels\LabelsResponseData;
use Arzcode\LaravelCorreos\Data\Labels\PrintLabelsRequestData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class PrintLabelsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected PrintLabelsRequestData $data,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/labels/print';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }

    public function createDtoFromResponse(Response $response): LabelsResponseData
    {
        return LabelsResponseData::from($response->json());
    }
}
