<?php

use Illuminate\Support\Facades\Cache;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SmartDato\CorreosShipping\Auth\CorreosAuthenticator;
use SmartDato\CorreosShipping\Connectors\PreregisterConnector;
use SmartDato\CorreosShipping\Connectors\TrackingConnector;
use SmartDato\CorreosShipping\Data\Preregister\DeliveryRequestData;
use SmartDato\CorreosShipping\Data\Preregister\DeliveryResponseData;
use SmartDato\CorreosShipping\Data\Tracking\ShipmentSearchResponseData;
use SmartDato\CorreosShipping\Exceptions\CorreosApiException;
use SmartDato\CorreosShipping\Resources\PreregisterResource;
use SmartDato\CorreosShipping\Resources\TrackingResource;

beforeEach(function (): void {
    Cache::put(
        new CorreosAuthenticator('id', 'secret', 'https://example.com/token', 'AP3', 'gw-id', 'gw-secret')->cacheKey(),
        'fake-test-token',
        3600,
    );
});

function retryAuthenticator(): CorreosAuthenticator
{
    return new CorreosAuthenticator('id', 'secret', 'https://example.com/token', 'AP3', 'gw-id', 'gw-secret');
}

/**
 * @param  array<int, MockResponse>  $responses
 * @return array{0: TrackingResource, 1: MockClient}
 */
function trackingResourceReturning(array $responses): array
{
    config()->set('correos-shipping-sdk.base_urls.tracking', 'https://api1.correos.es/support/trackpub/api/v2');

    $mockClient = new MockClient($responses);
    $connector = new TrackingConnector(retryAuthenticator(), retryInterval: 0);
    $connector->withMockClient($mockClient);

    return [new TrackingResource($connector), $mockClient];
}

/**
 * @param  array<int, MockResponse>  $responses
 * @return array{0: PreregisterResource, 1: MockClient}
 */
function preregisterResourceReturning(array $responses): array
{
    config()->set('correos-shipping-sdk.base_urls.preregister', 'https://api1.correos.es/admissions/preregister/api/v1');

    $mockClient = new MockClient($responses);
    $connector = new PreregisterConnector(retryAuthenticator(), retryInterval: 0);
    $connector->withMockClient($mockClient);

    return [new PreregisterResource($connector), $mockClient];
}

function retryShipmentRequest(): DeliveryRequestData
{
    return DeliveryRequestData::from(fixtureJson('preregister/delivery_request.json'));
}

it('retries a read that fails with a gateway error', function (): void {
    [$resource, $mockClient] = trackingResourceReturning([
        MockResponse::make(['message' => 'Service Unavailable'], 503),
        MockResponse::make(fixtureJson('tracking/search_response.json')),
    ]);

    $response = $resource->searchShipment('PQ1DR4A0000012345678');

    expect($response->code)->toBe('PQ1DR4A0000012345678');

    $mockClient->assertSentCount(2);
});

it('retries a write that was rate limited, because it was never processed', function (): void {
    [$resource, $mockClient] = preregisterResourceReturning([
        MockResponse::make(['message' => 'Too Many Requests'], 429),
        MockResponse::make(fixtureJson('preregister/delivery_response.json')),
    ]);

    $response = $resource->createShipments(retryShipmentRequest());

    expect($response->fileIdentifier)->toBe('FILE001');

    $mockClient->assertSentCount(2);
});

it('does not retry a write that failed with a gateway error, to avoid duplicate shipments', function (): void {
    [$resource, $mockClient] = preregisterResourceReturning([
        MockResponse::make(['message' => 'Gateway Timeout'], 504),
        MockResponse::make(fixtureJson('preregister/delivery_response.json')),
    ]);

    expect(fn (): DeliveryResponseData => $resource->createShipments(retryShipmentRequest()))
        ->toThrow(CorreosApiException::class);

    $mockClient->assertSentCount(1);
});

it('does not retry a rejected payload', function (): void {
    [$resource, $mockClient] = trackingResourceReturning([
        MockResponse::make(['message' => 'Bad Request'], 400),
        MockResponse::make(fixtureJson('tracking/search_response.json')),
    ]);

    expect(fn (): ShipmentSearchResponseData => $resource->searchShipment('PQ1DR4A0000012345678'))
        ->toThrow(CorreosApiException::class, 'Bad Request');

    $mockClient->assertSentCount(1);
});

it('gives up after the configured number of tries', function (): void {
    [$resource, $mockClient] = trackingResourceReturning([
        MockResponse::make(['message' => 'Service Unavailable'], 503),
        MockResponse::make(['message' => 'Service Unavailable'], 503),
        MockResponse::make(['message' => 'Service Unavailable'], 503),
    ]);

    expect(fn (): ShipmentSearchResponseData => $resource->searchShipment('PQ1DR4A0000012345678'))
        ->toThrow(CorreosApiException::class, 'Service Unavailable');

    $mockClient->assertSentCount(3);
});

it('sends no retries when they are switched off', function (): void {
    config()->set('correos-shipping-sdk.base_urls.tracking', 'https://api1.correos.es/support/trackpub/api/v2');

    $mockClient = new MockClient([
        MockResponse::make(['message' => 'Service Unavailable'], 503),
        MockResponse::make(fixtureJson('tracking/search_response.json')),
    ]);

    $connector = new TrackingConnector(retryAuthenticator(), tries: 1);
    $connector->withMockClient($mockClient);

    expect(fn (): ShipmentSearchResponseData => new TrackingResource($connector)->searchShipment('PQ1DR4A0000012345678'))
        ->toThrow(CorreosApiException::class);

    $mockClient->assertSentCount(1);
});
