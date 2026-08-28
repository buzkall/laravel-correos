<?php

use SmartDato\CorreosShipping\Enums\AdmissionMethod;
use SmartDato\CorreosShipping\Enums\DocumentationType;
use SmartDato\CorreosShipping\Enums\DoiType;
use SmartDato\CorreosShipping\Enums\ErrorCodeLanguage;
use SmartDato\CorreosShipping\Enums\LabelFormat;
use SmartDato\CorreosShipping\Enums\LabelOrderType;
use SmartDato\CorreosShipping\Enums\LabelPrintMode;
use SmartDato\CorreosShipping\Enums\ProductCode;
use SmartDato\CorreosShipping\Enums\ShipmentType;

dataset('enums', [
    AdmissionMethod::class,
    DocumentationType::class,
    DoiType::class,
    ErrorCodeLanguage::class,
    LabelFormat::class,
    LabelOrderType::class,
    LabelPrintMode::class,
    ProductCode::class,
    ShipmentType::class,
]);

it('labels every case', function (string $enum) {
    foreach ($enum::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
    }
})->with('enums');

it('offers value => label pairs for a select input', function (string $enum) {
    $options = $enum::options();

    expect($options)->toHaveCount(count($enum::cases()));

    // PHP coerces numeric string values into integer array keys, and looking
    // them up coerces the same way, so each case still finds its own label.
    foreach ($enum::cases() as $case) {
        expect($options[$case->value])->toBe($case->label());
    }
})->with('enums');

it('labels the label print modes readably', function () {
    expect(LabelPrintMode::options())->toBe([
        1 => 'A4 sheet',
        2 => 'Labeler',
    ]);
});

it('labels products with their commercial name', function () {
    expect(ProductCode::PaqPremium->label())->toBe('Paq Premium')
        ->and(ProductCode::options()['PAAZE'])->toBe('Paq Estándar');
});
