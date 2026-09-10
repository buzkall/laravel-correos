<?php

namespace Arzcode\LaravelCorreos\Requests\Labels;

use Arzcode\LaravelCorreos\Data\Labels\DocumentResponseData;
use Arzcode\LaravelCorreos\Data\Labels\PrintDocumentsRequestData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class PrintDocumentsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected PrintDocumentsRequestData $data,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/documents/print';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }

    public function createDtoFromResponse(Response $response): DocumentResponseData
    {
        return DocumentResponseData::from($response->json());
    }
}
