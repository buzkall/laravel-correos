<?php

namespace Arzcode\LaravelCorreos\Requests\Preregister;

use Arzcode\LaravelCorreos\Data\Preregister\BackofficeResponseData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetBackofficeTotalRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected ?string $contractNumber = null,
        protected ?string $clientNumber = null,
        protected ?string $dateFrom = null,
        protected ?string $dateTo = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/delivery/package/backoffice/total';
    }

    protected function defaultQuery(): array
    {
        // Only unset parameters are dropped: a bare array_filter() would also
        // throw away a legitimate '0'.
        return array_filter([
            'contractNumber' => $this->contractNumber,
            'clientNumber' => $this->clientNumber,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ], fn (?string $value): bool => $value !== null);
    }

    public function createDtoFromResponse(Response $response): BackofficeResponseData
    {
        return BackofficeResponseData::from($response->json());
    }
}
