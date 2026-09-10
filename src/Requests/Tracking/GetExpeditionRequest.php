<?php

namespace Arzcode\LaravelCorreos\Requests\Tracking;

use Arzcode\LaravelCorreos\Data\Tracking\ExpeditionResponseData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetExpeditionRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $expeditionCode,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/expedition/'.rawurlencode($this->expeditionCode);
    }

    public function createDtoFromResponse(Response $response): ExpeditionResponseData
    {
        return ExpeditionResponseData::from($response->json());
    }
}
