<?php

use Illuminate\Support\Facades\Cache;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SmartDato\CorreosShipping\Auth\CorreosAuthenticator;
use SmartDato\CorreosShipping\Connectors\LabelsConnector;
use SmartDato\CorreosShipping\Data\Labels\LabelsResponseData;
use SmartDato\CorreosShipping\Data\Labels\PrintLabelsRequestData;
use SmartDato\CorreosShipping\Exceptions\CorreosApiException;
use SmartDato\CorreosShipping\Requests\Labels\PrintLabelsRequest;
use SmartDato\CorreosShipping\Resources\LabelsResource;

beforeEach(function (): void {
    Cache::put(
        new CorreosAuthenticator('id', 'secret', 'https://example.com/token', 'AP3', 'gw-id', 'gw-secret')->cacheKey(),
        'fake-test-token',
        3600,
    );
});

/**
 * @param  array<string, mixed>|string  $body
 */
function errorException(array|string $body, int $status = 400): CorreosApiException
{
    config()->set('correos-shipping-sdk.base_urls.labels', 'https://api1.correos.es/support/labels/api/v1');

    // Retries are disabled so the failed response is returned rather than
    // thrown: this helper is about how an error body maps onto the exception.
    $connector = new LabelsConnector(
        new CorreosAuthenticator('id', 'secret', 'https://example.com/token', 'AP3', 'gw-id', 'gw-secret'),
        tries: 1,
    );
    $connector->withMockClient(new MockClient([
        PrintLabelsRequest::class => MockResponse::make($body, $status),
    ]));

    $response = $connector->send(new PrintLabelsRequest(PrintLabelsRequestData::from([
        'documentationType' => 1,
        'application' => 'OLC',
        'print' => [
            'shipments' => ['PQXYZ1234567890'],
            'labelFormat' => 2,
            'labelPrintMode' => 1,
        ],
    ])));

    $exception = $response->toException();

    if (! $exception instanceof CorreosApiException) {
        throw new RuntimeException('The connector did not build a CorreosApiException for the failed response.');
    }

    return $exception;
}

it('keeps string error fields as they are', function (): void {
    $exception = errorException([
        'message' => 'Bad Request',
        'code' => 'ERR-42',
        'moreInformation' => 'The shipment code is unknown',
    ]);

    expect($exception->getMessage())->toBe('Bad Request')
        ->and($exception->getCode())->toBe(400)
        ->and($exception->errorCode)->toBe('ERR-42')
        ->and($exception->moreInformation)->toBe('The shipment code is unknown');
});

it('joins a list of error details into one string', function (): void {
    $exception = errorException([
        'message' => 'Debe informar los campos obligatorios',
        'moreInformation' => ['application is mandatory', 'print is mandatory'],
    ]);

    expect($exception->moreInformation)->toBe('application is mandatory; print is mandatory');
});

it('encodes structured error details as json', function (): void {
    $exception = errorException([
        'message' => 'Validation failed',
        'moreInformation' => [
            ['field' => 'application', 'description' => 'mandatory'],
        ],
    ]);

    expect($exception->moreInformation)->toBe('[{"field":"application","description":"mandatory"}]');
});

it('renders an error message that is not a string', function (): void {
    $exception = errorException([
        'message' => ['first problem', 'second problem'],
    ]);

    expect($exception->getMessage())->toBe('first problem; second problem');
});

it('casts a numeric error code to a string', function (): void {
    $exception = errorException([
        'message' => 'Validation failed',
        'code' => 1234,
    ]);

    expect($exception->errorCode)->toBe('1234');
});

it('treats empty error details as absent', function (): void {
    $exception = errorException([
        'message' => 'Validation failed',
        'code' => '',
        'moreInformation' => [],
    ]);

    expect($exception->errorCode)->toBeNull()
        ->and($exception->moreInformation)->toBeNull();
});

it('falls back to the raw body when the response is not json', function (): void {
    $exception = errorException('<html>Gateway timeout</html>', 504);

    expect($exception->getMessage())->toBe('<html>Gateway timeout</html>')
        ->and($exception->getCode())->toBe(504)
        ->and($exception->moreInformation)->toBeNull();
});

it('throws the api exception itself rather than a generic dto failure', function (): void {
    Cache::put(
        new CorreosAuthenticator('id', 'secret', 'https://example.com/token', 'AP3', 'gw-id', 'gw-secret')->cacheKey(),
        'fake-test-token',
        3600,
    );
    config()->set('correos-shipping-sdk.base_urls.labels', 'https://api1.correos.es/support/labels/api/v1');

    $connector = new LabelsConnector(
        new CorreosAuthenticator('id', 'secret', 'https://example.com/token', 'AP3', 'gw-id', 'gw-secret')
    );
    $connector->withMockClient(new MockClient([
        PrintLabelsRequest::class => MockResponse::make([
            'message' => 'Debe informar los campos obligatorios',
            'code' => 'ERR-7',
            'moreInformation' => ['application is mandatory'],
        ], 400),
    ]));

    $resource = new LabelsResource($connector);

    $call = fn (): LabelsResponseData => $resource->printLabels(PrintLabelsRequestData::from([
        'documentationType' => 1,
        'application' => 'OLC',
        'print' => [
            'shipments' => ['PQXYZ1234567890'],
            'labelFormat' => 2,
            'labelPrintMode' => 1,
        ],
    ]));

    expect($call)->toThrow(CorreosApiException::class, 'Debe informar los campos obligatorios');

    try {
        $call();
    } catch (CorreosApiException $exception) {
        expect($exception->errorCode)->toBe('ERR-7')
            ->and($exception->moreInformation)->toBe('application is mandatory');
    }
});

it('keeps the failed response reachable so the caller can log it', function (): void {
    Cache::put(
        new CorreosAuthenticator('id', 'secret', 'https://example.com/token', 'AP3', 'gw-id', 'gw-secret')->cacheKey(),
        'fake-test-token',
        3600,
    );
    config()->set('correos-shipping-sdk.base_urls.labels', 'https://api1.correos.es/support/labels/api/v1');

    $connector = new LabelsConnector(
        new CorreosAuthenticator('id', 'secret', 'https://example.com/token', 'AP3', 'gw-id', 'gw-secret')
    );
    $connector->withMockClient(new MockClient([
        PrintLabelsRequest::class => MockResponse::make(['message' => 'Bad Request'], 400),
    ]));

    $resource = new LabelsResource($connector);

    try {
        $resource->printLabels(PrintLabelsRequestData::from([
            'documentationType' => 1,
            'application' => 'OLC',
            'print' => [
                'shipments' => ['PQXYZ1234567890'],
                'labelFormat' => 2,
                'labelPrintMode' => 1,
            ],
        ]));
    } catch (CorreosApiException) {
        // The caller logs the raw exchange from lastResponse().
    }

    expect($resource->lastResponse())->not->toBeNull()
        ->and($resource->lastResponse()->status())->toBe(400)
        ->and($resource->lastResponse()->body())->toBe('{"message":"Bad Request"}');
});
