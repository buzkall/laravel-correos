<?php

use SmartDato\CorreosShipping\Data\Labels\DocumentResponseData;
use SmartDato\CorreosShipping\Data\Labels\LabelsResponseData;
use SmartDato\CorreosShipping\Data\Labels\PrintLabelsRequestData;

it('creates print labels request data', function (): void {
    $data = PrintLabelsRequestData::from([
        'documentationType' => 1,
        'print' => [
            'shipments' => ['PQXYZ1234567890'],
            'labelFormat' => 2,
            'labelPrintMode' => 1,
        ],
    ]);

    expect($data->documentationType)->toBe(1)
        ->and($data->print->shipments)->toBe(['PQXYZ1234567890'])
        ->and($data->print->labelFormat)->toBe(2)
        ->and($data->print->labelPrintMode)->toBe(1);
});

it('serializes print labels request data without optional fields', function (): void {
    $data = PrintLabelsRequestData::from([
        'documentationType' => 0,
        'print' => [
            'shipments' => ['CODE1', 'CODE2'],
            'labelFormat' => 2,
            'labelPrintMode' => 1,
        ],
    ]);

    $array = $data->toArray();

    expect($array)->toHaveKey('documentationType', 0)
        ->toHaveKey('print')
        ->not->toHaveKey('application')
        ->and($array['print'])->toHaveKey('shipments', ['CODE1', 'CODE2'])
        ->toHaveKey('labelFormat', 2)->not->toHaveKey('clientLogo');
});

it('deserializes labels response data', function (): void {
    $data = LabelsResponseData::from(fixtureJson('labels/labels_response.json'));

    expect($data->pdf)->toBe('JVBERi0xLjQgZmFrZSBwZGYgY29udGVudA==')
        ->and($data->zpl)->toBeNull()
        ->and($data->xml)->toBeNull()
        ->and($data->error)->toBeNull();
});

it('decodes the base64 pdf into raw bytes', function (): void {
    $data = LabelsResponseData::from([
        'pdf' => base64_encode('%PDF-1.4 fake pdf content'),
        'zpl' => null,
        'xml' => null,
        'error' => null,
    ]);

    expect($data->decodedPdf())->toBe('%PDF-1.4 fake pdf content');
});

it('decodes the base64 pdf of a customs document', function (): void {
    $data = DocumentResponseData::from([
        'pdf' => base64_encode('%PDF-1.4 fake document'),
        'error' => null,
    ]);

    expect($data->decodedPdf())->toBe('%PDF-1.4 fake document');
});

it('has no decoded pdf when the response carries none', function (): void {
    $data = LabelsResponseData::from(['pdf' => null, 'zpl' => 'ZPL', 'xml' => null, 'error' => null]);

    expect($data->decodedPdf())->toBeNull();
});

it('has no decoded pdf when the payload is not base64', function (): void {
    $data = LabelsResponseData::from(['pdf' => 'not base64 !!', 'zpl' => null, 'xml' => null, 'error' => null]);

    expect($data->decodedPdf())->toBeNull();
});
