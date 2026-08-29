<?php

namespace SmartDato\CorreosShipping\Exceptions;

use JsonException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;
use Spatie\LaravelData\Optional;
use Throwable;

/**
 * Extends Saloon's RequestException so the connector retry policy can inspect
 * the failed response, and so callers already catching Saloon failures keep
 * catching these.
 */
class CorreosApiException extends RequestException
{
    public function __construct(
        Response $response,
        string $message,
        int $code = 0,
        public readonly ?string $errorCode = null,
        public readonly ?string $moreInformation = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($response, $message, $code, $previous);
    }

    public static function fromResponse(Response $response): self
    {
        try {
            /** @var mixed $data Saloon annotates json() as an array, but a scalar body decodes to a scalar. */
            $data = $response->json();
        } catch (JsonException) {
            $data = [];
        }

        if (! is_array($data)) {
            $data = [];
        }

        return new self(
            response: $response,
            message: self::flatten($data['message'] ?? null) ?? $response->body(),
            code: $response->status(),
            errorCode: self::flatten($data['code'] ?? null),
            moreInformation: self::flatten($data['moreInformation'] ?? null),
        );
    }

    /**
     * Correos answers part of its surface with HTTP 200 and an `error` payload
     * instead of an error status: printing a label for an unknown shipment
     * comes back as 200 with a null `pdf` and a filled `error`, which
     * `Response::throw()` cannot see. Anything carrying a non-empty top-level
     * `error` (or `errors`) is turned into the same exception an error status
     * would have produced. Returns null when the payload carries no error.
     *
     * Nested errors, such as the per-shipment validation errors of
     * `validateShipments()`, stay on the DTO — those are the answer, not a
     * failure.
     */
    public static function fromPayloadError(mixed $dto, Response $response): ?self
    {
        if (! is_object($dto)) {
            return null;
        }

        foreach (['error', 'errors'] as $property) {
            if (! property_exists($dto, $property)) {
                continue;
            }

            $error = get_object_vars($dto)[$property] ?? null;

            if (in_array($error, [null, '', []], true) || $error instanceof Optional) {
                continue;
            }

            return new self(
                response: $response,
                message: self::describeError($error) ?? 'The Correos API answered with an error payload.',
                code: $response->status(),
                errorCode: self::errorCodeFrom($error),
            );
        }

        return null;
    }

    private static function describeError(mixed $error): ?string
    {
        if (is_array($error)) {
            $parts = [];

            foreach ($error as $entry) {
                $part = self::describeErrorEntry($entry);

                if ($part !== null) {
                    $parts[] = $part;
                }
            }

            return $parts === [] ? null : implode('; ', $parts);
        }

        return self::describeErrorEntry($error);
    }

    private static function describeErrorEntry(mixed $entry): ?string
    {
        if (in_array($entry, [null, '', []], true)) {
            return null;
        }

        if (is_scalar($entry)) {
            return (string) $entry;
        }

        $fields = self::fieldsOf($entry);
        $description = self::flatten($fields['description'] ?? $fields['desError'] ?? $fields['message'] ?? null);
        $code = self::flatten(self::codeFieldOf($fields));

        return match (true) {
            $description !== null && $code !== null => $code.': '.$description,
            $description !== null => $description,
            $code !== null => $code,
            default => json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null,
        };
    }

    private static function errorCodeFrom(mixed $error): ?string
    {
        $first = is_array($error) ? (array_values($error)[0] ?? null) : $error;

        if ($first === null || is_scalar($first)) {
            return null;
        }

        return self::flatten(self::codeFieldOf(self::fieldsOf($first)));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private static function codeFieldOf(array $fields): mixed
    {
        return $fields['errorCode'] ?? $fields['codError'] ?? $fields['code'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function fieldsOf(mixed $entry): array
    {
        if (is_array($entry)) {
            /** @var array<string, mixed> $entry */
            return $entry;
        }

        return is_object($entry) ? get_object_vars($entry) : [];
    }

    /**
     * Correos answers these fields with a string on some endpoints and with a
     * list or an object of error details on others, so anything that is not a
     * string is rendered into one instead of blowing up on the type hint.
     */
    private static function flatten(mixed $value): ?string
    {
        if (in_array($value, [null, '', []], true)) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value) && count(array_filter($value, is_scalar(...))) === count($value)) {
            return implode('; ', array_map(strval(...), $value));
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
    }
}
