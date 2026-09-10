<?php

use Arzcode\LaravelCorreos\Auth\CorreosAuthenticator;
use Arzcode\LaravelCorreos\Connectors\PreregisterConnector;
use Arzcode\LaravelCorreos\Data\Preregister\AnnulmentRequestData;
use Arzcode\LaravelCorreos\Data\Preregister\DeliveryRequestData;
use Arzcode\LaravelCorreos\Data\Preregister\GenerateShipmentCodeRequestData;
use Arzcode\LaravelCorreos\Data\Preregister\QueryRequestData;
use Arzcode\LaravelCorreos\Requests\Preregister\CancelShipmentRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\CreateShipmentsRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\GenerateShipmentCodeRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\QueryShipmentsRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\ValidateShipmentsRequest;
use Arzcode\LaravelCorreos\Resources\PreregisterResource;
use Illuminate\Support\Facades\Cache;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    Cache::put(
        new CorreosAuthenticator('id', 'secret', 'https://example.com/token', 'AP3', 'gw-id', 'gw-secret')->cacheKey(),
        'fake-test-token',
        3600,
    );
});

function preregisterConnector(): PreregisterConnector
{
    config()->set('laravel-correos.base_urls.preregister', 'https://api1.correos.es/admissions/preregister/api/v1');

    return new PreregisterConnector(
        new CorreosAuthenticator('id', 'secret', 'https://example.com/token', 'AP3', 'gw-id', 'gw-secret')
    );
}

it('creates shipments and returns delivery response', function (): void {
    $mockClient = new MockClient([
        CreateShipmentsRequest::class => MockResponse::make(fixtureJson('preregister/delivery_response.json')),
    ]);

    $connector = preregisterConnector();
    $connector->withMockClient($mockClient);
    $resource = new PreregisterResource($connector);

    $requestData = DeliveryRequestData::from(fixtureJson('preregister/delivery_request.json'));
    $response = $resource->createShipments($requestData);

    expect($response->fileIdentifier)->toBe('FILE001')
        ->and($response->result)->toBe(1)
        ->and($response->shipments)->toHaveCount(1)
        ->and($response->shipments[0]->shipmentCode)->toBe('PQXYZ1234567890')
        ->and($response->shipments[0]->packages[0]->packageCode)->toBe('PQ1DR4A0000012345678');

    $mockClient->assertSent(CreateShipmentsRequest::class);
});

it('validates shipments', function (): void {
    $mockClient = new MockClient([
        ValidateShipmentsRequest::class => MockResponse::make(fixtureJson('preregister/delivery_response.json')),
    ]);

    $connector = preregisterConnector();
    $connector->withMockClient($mockClient);
    $resource = new PreregisterResource($connector);

    $requestData = DeliveryRequestData::from(fixtureJson('preregister/delivery_request.json'));
    $response = $resource->validateShipments($requestData);

    expect($response->result)->toBe(1);

    $mockClient->assertSent(ValidateShipmentsRequest::class);
});

it('queries shipments', function (): void {
    $mockClient = new MockClient([
        QueryShipmentsRequest::class => MockResponse::make(fixtureJson('preregister/query_response.json')),
    ]);

    $connector = preregisterConnector();
    $connector->withMockClient($mockClient);
    $resource = new PreregisterResource($connector);

    $queryData = QueryRequestData::from(['shipments' => ['PQ1DR4A0000012345678']]);
    $response = $resource->queryShipments($queryData);

    expect($response->shipments)->toHaveCount(1)
        ->and($response->shipments[0]->shipmentCode)->toBe('PQXYZ1234567890');

    $mockClient->assertSent(QueryShipmentsRequest::class);
});

it('cancels a shipment', function (): void {
    $mockClient = new MockClient([
        CancelShipmentRequest::class => MockResponse::make(fixtureJson('preregister/annulment_response.json')),
    ]);

    $connector = preregisterConnector();
    $connector->withMockClient($mockClient);
    $resource = new PreregisterResource($connector);

    $annulmentData = AnnulmentRequestData::from(['packageCode' => 'PQ1DR4A0000012345678']);
    $response = $resource->cancelShipment($annulmentData);

    expect($response->message)->toBe('Shipment cancelled successfully');

    $mockClient->assertSent(CancelShipmentRequest::class);
});

it('generates a shipment code', function (): void {
    $mockClient = new MockClient([
        GenerateShipmentCodeRequest::class => MockResponse::make(fixtureJson('preregister/generate_response.json')),
    ]);

    $connector = preregisterConnector();
    $connector->withMockClient($mockClient);
    $resource = new PreregisterResource($connector);

    $generateData = GenerateShipmentCodeRequestData::from([
        'contractNumber' => '12345678',
        'clientNumber' => '1234567890',
        'labellerCode' => '0001',
        'packagesNumber' => '1',
        'product' => 'PAFXB',
        'deliveryMethod' => 'DOUAOF',
    ]);
    $response = $resource->generateShipmentCode($generateData);

    expect($response->result)->toBe(1)
        ->and($response->shipmentCode)->toBe('PQXYZ9876543210');

    $mockClient->assertSent(GenerateShipmentCodeRequest::class);
});
