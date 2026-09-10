<?php

use Arzcode\LaravelCorreos\Auth\CorreosAuthenticator;
use Arzcode\LaravelCorreos\Connectors\LabelsConnector;
use Arzcode\LaravelCorreos\Connectors\PreregisterConnector;
use Arzcode\LaravelCorreos\Connectors\TrackingConnector;
use Arzcode\LaravelCorreos\Data\Labels\LabelsResponseData;
use Arzcode\LaravelCorreos\Data\Labels\PrintLabelsRequestData;
use Arzcode\LaravelCorreos\Data\Preregister\AnnulmentRequestData;
use Arzcode\LaravelCorreos\Data\Preregister\AnnulmentResponseData;
use Arzcode\LaravelCorreos\Data\Preregister\DeliveryRequestData;
use Arzcode\LaravelCorreos\Data\Preregister\QueryRequestData;
use Arzcode\LaravelCorreos\Data\Preregister\QueryResponseData;
use Arzcode\LaravelCorreos\Data\Tracking\ShipmentSearchResponseData;
use Arzcode\LaravelCorreos\Exceptions\CorreosApiException;
use Arzcode\LaravelCorreos\Requests\Labels\PrintLabelsRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\CancelShipmentRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\QueryShipmentsRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\ValidateShipmentsRequest;
use Arzcode\LaravelCorreos\Requests\Tracking\SearchShipmentRequest;
use Arzcode\LaravelCorreos\Resources\LabelsResource;
use Arzcode\LaravelCorreos\Resources\PreregisterResource;
use Arzcode\LaravelCorreos\Resources\TrackingResource;
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

function payloadErrorAuthenticator(): CorreosAuthenticator
{
    return new CorreosAuthenticator('id', 'secret', 'https://example.com/token', 'AP3', 'gw-id', 'gw-secret');
}

function labelsResourceAnswering(MockClient $mockClient): LabelsResource
{
    config()->set('laravel-correos.base_urls.labels', 'https://api1.correos.es/support/labels/api/v1');

    $connector = new LabelsConnector(payloadErrorAuthenticator());
    $connector->withMockClient($mockClient);

    return new LabelsResource($connector);
}

function preregisterResourceAnswering(MockClient $mockClient): PreregisterResource
{
    config()->set('laravel-correos.base_urls.preregister', 'https://api1.correos.es/admissions/preregister/api/v1');

    $connector = new PreregisterConnector(payloadErrorAuthenticator());
    $connector->withMockClient($mockClient);

    return new PreregisterResource($connector);
}

function trackingResourceAnswering(MockClient $mockClient): TrackingResource
{
    config()->set('laravel-correos.base_urls.tracking', 'https://api1.correos.es/support/trackpub/api/v2');

    $connector = new TrackingConnector(payloadErrorAuthenticator());
    $connector->withMockClient($mockClient);

    return new TrackingResource($connector);
}

function payloadErrorShipmentRequest(): DeliveryRequestData
{
    return DeliveryRequestData::from(fixtureJson('preregister/delivery_request.json'));
}

function printLabelsData(): PrintLabelsRequestData
{
    return PrintLabelsRequestData::from([
        'documentationType' => 1,
        'print' => [
            'shipments' => ['PQXYZ1234567890'],
            'labelFormat' => 2,
            'labelPrintMode' => 1,
        ],
    ]);
}

it('throws when the labels endpoint answers 200 with an error and no pdf', function (): void {
    $resource = labelsResourceAnswering(new MockClient([
        PrintLabelsRequest::class => MockResponse::make([
            'pdf' => null,
            'zpl' => null,
            'xml' => null,
            'error' => 'El envío PQXYZ1234567890 no existe',
        ], 200),
    ]));

    expect(fn (): LabelsResponseData => $resource->printLabels(printLabelsData()))
        ->toThrow(CorreosApiException::class, 'El envío PQXYZ1234567890 no existe');
});

it('keeps the 200 error response reachable on the exception and the resource', function (): void {
    $resource = labelsResourceAnswering(new MockClient([
        PrintLabelsRequest::class => MockResponse::make([
            'pdf' => null,
            'zpl' => null,
            'xml' => null,
            'error' => 'El envío no existe',
        ], 200),
    ]));

    try {
        $resource->printLabels(printLabelsData());
    } catch (CorreosApiException $exception) {
        expect($exception->getCode())->toBe(200)
            ->and($exception->getResponse()->json('error'))->toBe('El envío no existe');
    }

    expect($resource->lastResponse())->not->toBeNull()
        ->and($resource->lastResponse()->status())->toBe(200);
});

it('throws when a preregister query answers 200 with an error', function (): void {
    $resource = preregisterResourceAnswering(new MockClient([
        QueryShipmentsRequest::class => MockResponse::make([
            'shipments' => null,
            'error' => 'Contrato no autorizado',
        ], 200),
    ]));

    expect(fn (): QueryResponseData => $resource->queryShipments(QueryRequestData::from(['shipments' => ['PQXYZ1234567890']])))
        ->toThrow(CorreosApiException::class, 'Contrato no autorizado');
});

it('renders a list of error objects into one message and keeps the first code', function (): void {
    $resource = preregisterResourceAnswering(new MockClient([
        CancelShipmentRequest::class => MockResponse::make([
            'message' => null,
            'errors' => [
                ['errorCode' => 1021, 'description' => 'El envío ya está anulado', 'errorFieldName' => 'packageCode'],
                ['errorCode' => 1022, 'description' => 'Contrato no autorizado', 'errorFieldName' => null],
            ],
        ], 200),
    ]));

    $call = fn (): AnnulmentResponseData => $resource->cancelShipment(AnnulmentRequestData::from(['packageCode' => 'PQ1DR4A0000012345678']));

    expect($call)->toThrow(CorreosApiException::class, '1021: El envío ya está anulado; 1022: Contrato no autorizado');

    try {
        $call();
    } catch (CorreosApiException $exception) {
        expect($exception->errorCode)->toBe('1021');
    }
});

it('throws when tracking answers 200 with an error list', function (): void {
    $resource = trackingResourceAnswering(new MockClient([
        SearchShipmentRequest::class => MockResponse::make([
            'shipment' => null,
            'error' => [
                ['codError' => '1001', 'desError' => 'Envío no encontrado'],
            ],
        ], 200),
    ]));

    expect(fn (): ShipmentSearchResponseData => $resource->searchShipment('PQ1DR4A0000012345678'))
        ->toThrow(CorreosApiException::class, '1001: Envío no encontrado');
});

it('leaves per shipment validation errors on the dto', function (): void {
    $resource = preregisterResourceAnswering(new MockClient([
        ValidateShipmentsRequest::class => MockResponse::make([
            'fileIdentifier' => 'FILE001',
            'result' => 1,
            'shipments' => [
                [
                    'validationErrorCount' => 1,
                    'shipmentCode' => null,
                    'entryDate' => null,
                    'packages' => null,
                    'error' => [
                        ['errorCode' => 2001, 'description' => 'CP no válido', 'errorFieldName' => 'cp'],
                    ],
                ],
            ],
        ], 200),
    ]));

    $response = $resource->validateShipments(payloadErrorShipmentRequest());

    expect($response->shipments[0]->validationErrorCount)->toBe(1)
        ->and($response->shipments[0]->error[0]->description)->toBe('CP no válido');
});

it('does not throw when the error field is empty', function (): void {
    $resource = labelsResourceAnswering(new MockClient([
        PrintLabelsRequest::class => MockResponse::make([
            'pdf' => 'JVBERi0xLjQ=',
            'zpl' => null,
            'xml' => null,
            'error' => '',
        ], 200),
    ]));

    expect($resource->printLabels(printLabelsData())->pdf)->toBe('JVBERi0xLjQ=');
});
