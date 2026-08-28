<?php

use Illuminate\Support\Facades\Cache;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SmartDato\CorreosShipping\Auth\CorreosAuthenticator;
use SmartDato\CorreosShipping\Connectors\LabelsConnector;
use SmartDato\CorreosShipping\Data\Labels\PrintDocumentsRequestData;
use SmartDato\CorreosShipping\Data\Labels\PrintLabelsRequestData;
use SmartDato\CorreosShipping\Requests\Labels\PrintDocumentsRequest;
use SmartDato\CorreosShipping\Requests\Labels\PrintLabelsRequest;
use SmartDato\CorreosShipping\Resources\LabelsResource;

beforeEach(function (): void {
    Cache::put(
        new CorreosAuthenticator('id', 'secret', 'https://example.com/token', 'AP3', 'gw-id', 'gw-secret')->cacheKey(),
        'fake-test-token',
        3600,
    );
});

function labelsConnector(): LabelsConnector
{
    config()->set('correos-shipping-sdk.base_urls.labels', 'https://api1.correos.es/support/labels/api/v1');

    return new LabelsConnector(
        new CorreosAuthenticator('id', 'secret', 'https://example.com/token', 'AP3', 'gw-id', 'gw-secret')
    );
}

it('prints labels and returns pdf', function (): void {
    $mockClient = new MockClient([
        PrintLabelsRequest::class => MockResponse::make(
            fixtureJson('labels/labels_response.json')
        ),
    ]);

    $connector = labelsConnector();
    $connector->withMockClient($mockClient);
    $resource = new LabelsResource($connector);

    $requestData = PrintLabelsRequestData::from([
        'documentationType' => 1,
        'print' => [
            'shipments' => ['PQXYZ1234567890'],
            'labelFormat' => 2,
            'labelPrintMode' => 1,
        ],
    ]);

    $response = $resource->printLabels($requestData);

    expect($response->pdf)->not->toBeNull()
        ->and($response->error)->toBeNull();

    $mockClient->assertSent(PrintLabelsRequest::class);
});

it('prints documents and returns pdf', function (): void {
    $mockClient = new MockClient([
        PrintDocumentsRequest::class => MockResponse::make(
            fixtureJson('labels/document_response.json')
        ),
    ]);

    $connector = labelsConnector();
    $connector->withMockClient($mockClient);
    $resource = new LabelsResource($connector);

    $requestData = PrintDocumentsRequestData::from([
        'documentationType' => 5,
        'documentData' => [
            'destinationName' => 'France',
        ],
    ]);

    $response = $resource->printDocuments($requestData);

    expect($response->pdf)->not->toBeNull()
        ->and($response->error)->toBeNull();

    $mockClient->assertSent(PrintDocumentsRequest::class);
});
