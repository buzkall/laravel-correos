<?php

use SmartDato\CorreosShipping\Enums\AdmissionMethod;
use SmartDato\CorreosShipping\Enums\Contracts\Optionable;
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

/**
 * Datasets hand the test a plain string, so narrow it back to the contract the
 * enums implement before calling their statics.
 *
 * @return class-string<Optionable>
 */
function optionableEnum(string $enum): string
{
    if (! is_a($enum, Optionable::class, true)) {
        throw new RuntimeException("{$enum} does not implement ".Optionable::class.'.');
    }

    return $enum;
}

it('labels every case', function (string $enum): void {
    foreach (optionableEnum($enum)::cases() as $case) {
        expect($case->label())->not->toBeEmpty();
    }
})->with('enums');

it('offers value => label pairs for a select input', function (string $enum): void {
    $enum = optionableEnum($enum);

    $cases = $enum::cases();
    $options = $enum::options();

    expect($options)->toHaveSameSize($cases);

    // PHP coerces numeric string values into integer array keys, and looking
    // them up coerces the same way, so each case still finds its own label.
    foreach ($cases as $case) {
        expect($options)->toHaveKey($case->value, $case->label());
    }
})->with('enums');

it('labels the label print modes readably', function (): void {
    expect(LabelPrintMode::options())->toBe([
        1 => 'A4 sheet',
        2 => 'Labeler',
    ]);
});

it('labels products with their commercial name', function (): void {
    expect(ProductCode::PaqPremium->label())->toBe('Paq Premium')
        ->and(ProductCode::options()['PAAZE'])->toBe('Paq Estándar');
});
