<?php

namespace Arzcode\LaravelCorreos\Requests\Preregister;

use Arzcode\LaravelCorreos\Data\Preregister\PackageReferenceResponseData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetPackagesByReferenceRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $clientReference,
        protected ?string $contractNumber = null,
        protected ?string $clientNumber = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/delivery/package/reference';
    }

    protected function defaultQuery(): array
    {
        // Only unset parameters are dropped: a bare array_filter() would also
        // throw away a legitimate '0'.
        return array_filter([
            'clientReference' => $this->clientReference,
            'contractNumber' => $this->contractNumber,
            'clientNumber' => $this->clientNumber,
        ], fn (?string $value): bool => $value !== null);
    }

    public function createDtoFromResponse(Response $response): PackageReferenceResponseData
    {
        return PackageReferenceResponseData::from(['packageCodes' => $response->json()]);
    }
}
