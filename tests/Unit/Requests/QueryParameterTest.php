<?php

use Arzcode\LaravelCorreos\Requests\Preregister\GetBackofficeErrorsRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\GetPackagesByReferenceRequest;

it('drops unset query parameters but keeps a zero', function (): void {
    $request = new GetPackagesByReferenceRequest('0', clientNumber: '0');

    expect($request->query()->all())->toBe([
        'clientReference' => '0',
        'clientNumber' => '0',
    ]);
});

it('sends no query parameters when none are given', function (): void {
    expect(new GetBackofficeErrorsRequest()->query()->all())->toBe([]);
});
