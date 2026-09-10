<?php

use Arzcode\LaravelCorreos\Requests\Labels\GetDocumentBackofficeRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\GetBackofficeShipmentRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\GetExpeditionPackagesRequest;
use Arzcode\LaravelCorreos\Requests\Tracking\GetExpeditionRequest;
use Arzcode\LaravelCorreos\Requests\Tracking\SearchShipmentRequest;

/**
 * Shipment and expedition codes reach the SDK straight from user input, so a
 * code carrying `../`, `?` or `#` must not be able to reshape the URL and send
 * the request — with its credentials attached — somewhere else.
 */
it('encodes path parameters instead of building the url from raw input', function (): void {
    $code = '../../admin?leak=1';
    $encoded = '..%2F..%2Fadmin%3Fleak%3D1';

    expect(new SearchShipmentRequest($code)->resolveEndpoint())->toBe('/search/'.$encoded)
        ->and(new GetExpeditionRequest($code)->resolveEndpoint())->toBe('/expedition/'.$encoded)
        ->and(new GetDocumentBackofficeRequest($code)->resolveEndpoint())->toBe('/documents/backoffice/'.$encoded)
        ->and(new GetBackofficeShipmentRequest($code)->resolveEndpoint())->toBe('/delivery/package/backoffice/shipment/'.$encoded)
        ->and(new GetExpeditionPackagesRequest($code)->resolveEndpoint())->toBe('/delivery/package/expedition/'.$encoded);
});

it('leaves an ordinary code untouched', function (): void {
    expect(new SearchShipmentRequest('PK0123456789ES')->resolveEndpoint())
        ->toBe('/search/PK0123456789ES');
});
