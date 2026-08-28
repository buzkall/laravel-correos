<?php

namespace SmartDato\CorreosShipping\Resources;

use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Http\Response;
use SmartDato\CorreosShipping\Exceptions\CorreosApiException;

abstract class CorreosResource
{
    protected ?Response $lastResponse = null;

    public function __construct(
        protected Connector $connector,
    ) {}

    /**
     * The raw response of the last call, so callers can log the exchange.
     */
    public function lastResponse(): ?Response
    {
        return $this->lastResponse;
    }

    /**
     * Send a request and hydrate its DTO, failing both on error statuses and
     * on the 200-with-error payloads Correos answers with on part of its
     * surface.
     */
    protected function send(Request $request): mixed
    {
        $this->lastResponse = null;

        try {
            $response = $this->lastResponse = $this->connector->send($request);
        } catch (RequestException $exception) {
            // With retries enabled Saloon throws failed responses itself, so the
            // response is recovered from the exception to keep it loggable.
            $this->lastResponse = $exception->getResponse();

            throw $exception;
        }

        $dto = $response->throw()->dto();

        $payloadError = CorreosApiException::fromPayloadError($dto, $response);

        if ($payloadError instanceof CorreosApiException) {
            throw $payloadError;
        }

        return $dto;
    }
}
