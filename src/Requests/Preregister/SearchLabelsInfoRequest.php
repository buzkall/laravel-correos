<?php

namespace Arzcode\LaravelCorreos\Requests\Preregister;

use Arzcode\LaravelCorreos\Data\Preregister\LabelsInfoResponseData;
use Arzcode\LaravelCorreos\Data\Preregister\SearchLabelsInfoRequestData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class SearchLabelsInfoRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected SearchLabelsInfoRequestData $data,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/labels/info/search';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }

    public function createDtoFromResponse(Response $response): LabelsInfoResponseData
    {
        return LabelsInfoResponseData::from($response->json());
    }
}
