<?php

namespace SmartDato\CorreosShipping\Exceptions;

use Exception;
use Saloon\Http\Response;

class CorreosApiException extends Exception
{
    public function __construct(
        string $message,
        int $code = 0,
        public readonly ?string $errorCode = null,
        public readonly ?string $moreInformation = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromResponse(Response $response): self
    {
        try {
            /** @var mixed $data Saloon annotates json() as an array, but a scalar body decodes to a scalar. */
            $data = $response->json();
        } catch (\JsonException) {
            $data = [];
        }

        if (! is_array($data)) {
            $data = [];
        }

        return new self(
            message: self::flatten($data['message'] ?? null) ?? $response->body(),
            code: $response->status(),
            errorCode: self::flatten($data['code'] ?? null),
            moreInformation: self::flatten($data['moreInformation'] ?? null),
        );
    }

    /**
     * Correos answers these fields with a string on some endpoints and with a
     * list or an object of error details on others, so anything that is not a
     * string is rendered into one instead of blowing up on the type hint.
     */
    private static function flatten(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value) && count(array_filter($value, 'is_scalar')) === count($value)) {
            return implode('; ', array_map(strval(...), $value));
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
    }
}
