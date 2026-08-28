# Correos Shipping SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/smart-dato/correos-shipping-sdk.svg?style=flat-square)](https://packagist.org/packages/smart-dato/correos-shipping-sdk)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/smart-dato/correos-shipping-sdk/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/smart-dato/correos-shipping-sdk/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/smart-dato/correos-shipping-sdk/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/smart-dato/correos-shipping-sdk/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/smart-dato/correos-shipping-sdk.svg?style=flat-square)](https://packagist.org/packages/smart-dato/correos-shipping-sdk)

Laravel package for integrating with the Correos (Spanish postal service) APIs. Supports shipment preregistration, label and customs document generation, and tracking. Built on [Saloon 4.x](https://docs.saloon.dev) for HTTP and [Spatie Laravel Data 4.x](https://spatie.be/docs/laravel-data) for DTOs.

## Requirements

- PHP 8.4+
- Laravel 11, 12 or 13

Collections (*recogidas*) are not covered: the API has no resource for them yet, so shipments are handed over at an office or picked up under a standing agreement.

## Installation

Install the package via Composer:

```bash
composer require smart-dato/correos-shipping-sdk
```

Publish the config file:

```bash
php artisan vendor:publish --tag="correos-shipping-sdk-config"
```

## Configuration

Add the following environment variables to your `.env` file:

```env
# OAuth credentials (CorreosID)
CORREOS_OAUTH_CLIENT_ID=your-oauth-client-id
CORREOS_OAUTH_CLIENT_SECRET=your-oauth-client-secret

# API Gateway credentials
CORREOS_GATEWAY_CLIENT_ID=your-gateway-client-id
CORREOS_GATEWAY_CLIENT_SECRET=your-gateway-client-secret
```

The published config file (`config/correos-shipping-sdk.php`) contains all available options:

```php
return [
    'oauth' => [
        'client_id'     => env('CORREOS_OAUTH_CLIENT_ID'),
        'client_secret' => env('CORREOS_OAUTH_CLIENT_SECRET'),
        'token_url'     => env('CORREOS_TOKEN_URL', 'https://apioauthcid.correos.es/Api/Authorize/Token'),
        'scope'         => env('CORREOS_OAUTH_SCOPE', 'AP3 LBS RCG'),
    ],
    'gateway' => [
        'client_id'     => env('CORREOS_GATEWAY_CLIENT_ID'),
        'client_secret' => env('CORREOS_GATEWAY_CLIENT_SECRET'),
    ],
    'base_urls' => [
        'preregister' => env('CORREOS_PREREGISTER_URL', 'https://api1.correos.es/admissions/preregister/api/v1'),
        'labels'      => env('CORREOS_LABELS_URL', 'https://api1.correos.es/support/labels/api/v1'),
        'tracking'    => env('CORREOS_TRACKING_URL', 'https://api1.correos.es/support/trackpub/api/v2'),
    ],
    'verify_ssl' => env('CORREOS_VERIFY_SSL', true),
    'force_ip_resolve' => env('CORREOS_FORCE_IP_RESOLVE'),
    'retry' => [
        'times'               => env('CORREOS_RETRY_TIMES', 3),
        'interval'            => env('CORREOS_RETRY_INTERVAL', 500), // milliseconds
        'exponential_backoff' => env('CORREOS_RETRY_EXPONENTIAL_BACKOFF', true),
    ],
    'timeout' => env('CORREOS_TIMEOUT'),                  // seconds; Saloon default 30
    'connect_timeout' => env('CORREOS_CONNECT_TIMEOUT'),  // seconds; Saloon default 10
    'user_agent' => env('CORREOS_USER_AGENT'),
];
```

For the **pre-production** environment, override the URLs:

```env
CORREOS_TOKEN_URL=https://apioauthcid.correospre.es/Api/Authorize/Token
CORREOS_PREREGISTER_URL=https://api1.correospre.es/admissions/preregister/api/v1
CORREOS_LABELS_URL=https://api1.correospre.es/support/labels/api/v1
CORREOS_TRACKING_URL=https://api1.correospre.es/support/trackpub/api/v2
```

If the pre-production environment uses self-signed certificates, you can disable SSL verification:

```env
CORREOS_VERIFY_SSL=false
```

> **Warning:** Never disable SSL verification in production.

If the pre-production environment only allows IPv4 connections (e.g., CloudFront blocks IPv6), you can force IPv4 resolution:

```env
CORREOS_FORCE_IP_RESOLVE=v4
```

### Network access

Correos whitelists the client IP for the pre-production environment: connections from a
non-whitelisted address (and any IPv6 address, which CloudFront answers with a `403`) are
rejected before they reach the API, and PRE is only up Monday to Friday, 08:00–20:00 CET.
Confirm with your Correos commercial contact whether your production contract carries the
same restriction; if it does, every host that calls the API — web servers, queue workers,
scheduled jobs — has to egress from a fixed, whitelisted IPv4 address, which usually means
pinning them to a static IP or routing them through a NAT gateway.

## Usage

Resolve the SDK from the container (or use the `CorreosShipping` facade):

```php
use SmartDato\CorreosShipping\CorreosShipping;

$correos = app(CorreosShipping::class);
```

### Preregister Shipments

```php
use SmartDato\CorreosShipping\Data\Preregister\DeliveryRequestData;

$request = DeliveryRequestData::from([
    'shipments' => [
        [
            'product' => 'PAFXB',
            'deliveryMethod' => 'DOUAOF',
            'contractNumber' => '12345678',
            'clientNumber' => '1234567890',
            'labellerCode' => '0001',
            'packagesNumber' => '1',
            'sender' => [
                'name' => 'My Company',
                'address' => 'Calle Sender 1',
                'locality' => 'Madrid',
                'province' => '28',
                'cp' => '28001',
                'country' => 'ESP',
            ],
            'addressee' => [
                'name' => 'John Doe',
                'address' => 'Calle Receiver 2',
                'locality' => 'Barcelona',
                'province' => '08',
                'cp' => '08001',
                'country' => 'ESP',
            ],
            'packages' => [
                ['packageWeightGrams' => '500'],
            ],
        ],
    ],
]);

// Validate before creating
$validation = $correos->preregister()->validateShipments($request);

// Create the shipment
$response = $correos->preregister()->createShipments($request);

$response->fileIdentifier;  // "FILE001"
$response->shipments[0]->shipmentCode;  // "PQXYZ1234567890"
$response->shipments[0]->packages[0]->packageCode;  // "PQ1DR4A0000012345678"
```

### Print Labels

```php
use SmartDato\CorreosShipping\Data\Labels\PrintLabelsRequestData;

$labelRequest = PrintLabelsRequestData::from([
    'documentationType' => 1, // 0=All, 1=Label, 2=CN22/CN23
    'print' => [
        'shipments' => ['PQXYZ1234567890'],
        'labelFormat' => 2,    // 1=XML, 2=PDF, 3=ZPL
        'labelPrintMode' => 1, // 1=A4, 2=Labeler
    ],
]);

$labels = $correos->labels()->printLabels($labelRequest);

$labels->pdf;            // Base64-encoded PDF content
$labels->decodedPdf();   // The same PDF as raw bytes, or null if there is none
```

`labelPrintMode` decides what that PDF contains, and the two modes are not interchangeable:

- `1` (A4) returns a full A4 page with the labels already laid out on the sheet.
- `2` (labeler) returns one label per page, at label size.

To place labels yourself on an A4 sheet — starting at an arbitrary cell, or mixing carriers on
one sheet — ask for mode `2` and compose the page with FPDI; mode `1` gives you a page you
would have to cut up again:

```php
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

$pdf = new Fpdi;
$pdf->AddPage();

$pages = $pdf->setSourceFile(
    StreamReader::createByString($labels->decodedPdf())
);

// 2 columns x 4 rows of 105mm x 74.25mm cells on A4.
foreach (range(1, $pages) as $cell => $page) {
    $pdf->useTemplate(
        $pdf->importPage($page),
        x: ($cell % 2) * 105,
        y: intdiv($cell, 2) * 74.25,
        width: 105,
    );
}
```

### Print Customs Documents (DCAF/DDP)

```php
use SmartDato\CorreosShipping\Data\Labels\PrintDocumentsRequestData;

$docRequest = PrintDocumentsRequestData::from([
    'documentationType' => 5, // 5=DCAF, 6=DDP
    'documentData' => [
        'destinationName' => 'France',
        'contractNumber' => '12345678',
        'clientNumber' => '1234567890',
    ],
]);

$document = $correos->labels()->printDocuments($docRequest);

$document->pdf;  // Base64-encoded PDF
```

### Track Shipments

```php
$tracking = $correos->tracking()->searchShipment('PQ1DR4A0000012345678');

$tracking->code;          // "PQ1DR4A0000012345678"
$tracking->codProduct;    // "PQDOM"
$tracking->remitName;     // Sender name
$tracking->destiName;     // Addressee name
$tracking->events;        // Array of TrackingEventData

foreach ($tracking->events as $event) {
    $event->eventDate;     // "06/02/2026"
    $event->eventCode;     // "P010000V"
    $event->summaryText;   // "Shipment preregistered"
    $event->location;      // "CTA MADRID"
}
```

### Track Expeditions

```php
$expedition = $correos->tracking()->getExpedition('EXP001234567890');

$expedition->refExpedition;       // "EXP001234567890"
$expedition->serviceDescription;  // "Paq Premium"
$expedition->clients;             // Array of ExpeditionClientData
$expedition->packages;            // Array of ExpeditionPackageData
```

### Other Preregister Operations

```php
// Cancel a shipment
$correos->preregister()->cancelShipment(
    AnnulmentRequestData::from(['packageCode' => 'PQ1DR4A0000012345678'])
);

// Cancel an expedition
$correos->preregister()->cancelExpedition(
    AnnulmentExpeditionRequestData::from(['expeditionCode' => 'EXP001234567890'])
);

// Generate shipment codes
$correos->preregister()->generateShipmentCode(
    GenerateShipmentCodeRequestData::from([
        'contractNumber' => '12345678',
        'clientNumber' => '1234567890',
        'labellerCode' => '0001',
        'packagesNumber' => '1',
        'product' => 'PAFXB',
        'deliveryMethod' => 'DOUAOF',
    ])
);

// Modify a shipment
$correos->preregister()->modifyShipment($deliveryRequestData);

// Query shipments
$correos->preregister()->queryShipments(
    QueryRequestData::from(['shipments' => ['PQ1DR4A0000012345678']])
);

// Get expedition packages
$correos->preregister()->getExpeditionPackages('EXP001234567890');

// Search by client reference
$correos->preregister()->getPackagesByReference('MY-REF-001');

// Backoffice queries
$correos->preregister()->getBackofficeShipment('PQXYZ1234567890');
$correos->preregister()->getBackofficeErrors(contractNumber: '12345678');
$correos->preregister()->getBackofficeTotal(dateFrom: '01/01/2026', dateTo: '31/01/2026');
$correos->preregister()->getBackofficeWaiting();
```

### Using the Facade

```php
use SmartDato\CorreosShipping\Facades\CorreosShipping;

$response = CorreosShipping::preregister()->createShipments($request);
$labels = CorreosShipping::labels()->printLabels($labelRequest);
$tracking = CorreosShipping::tracking()->searchShipment('PQ1DR4A0000012345678');
```

## Available Enums

The package provides typed enums for API constants:

```php
use SmartDato\CorreosShipping\Enums\DocumentationType;  // All, Label, CN22_CN23, DCAF, DDP
use SmartDato\CorreosShipping\Enums\LabelFormat;         // XML, PDF, ZPL
use SmartDato\CorreosShipping\Enums\LabelPrintMode;      // A4, Labeler
use SmartDato\CorreosShipping\Enums\LabelOrderType;      // InternationalPoBox, Company, LastName, PackageId, ClientReference
use SmartDato\CorreosShipping\Enums\ShipmentType;        // Documents, Goods, Gift, Samples, Returns, Other, Dangerous
use SmartDato\CorreosShipping\Enums\DoiType;             // European, DNI, NIE, Other, CIF
use SmartDato\CorreosShipping\Enums\AdmissionMethod;     // Office, Citypaq, DeliveryUnit
use SmartDato\CorreosShipping\Enums\ErrorCodeLanguage;   // Spanish, English
```

Each case carries a human readable `label()`, and every enum exposes `options()` — value =>
label pairs, ready for a select input:

```php
ProductCode::PaqPremium->label();  // "Paq Premium"
LabelPrintMode::options();         // [1 => 'A4 sheet', 2 => 'Labeler']
```

## Error Handling

API errors are thrown as `CorreosApiException`, which extends Saloon's `RequestException`:

```php
use SmartDato\CorreosShipping\Exceptions\CorreosApiException;

try {
    $response = $correos->preregister()->createShipments($request);
} catch (CorreosApiException $e) {
    $e->getMessage();        // Error message from the API
    $e->getCode();           // HTTP status code
    $e->errorCode;           // Correos error code
    $e->moreInformation;     // Additional error details
    $e->getResponse();       // The raw Saloon response, for logging
}
```

### Errors returned with a 200

Part of the Correos surface answers failures with HTTP 200 and an `error` field rather than
an error status — printing a label for an unknown shipment comes back as `200` with a null
`pdf` and a filled `error`. Those payloads are turned into the same `CorreosApiException`, so
a call that returns a DTO has returned a usable one:

```php
$labels = $correos->labels()->printLabels($labelRequest);

// Never reached when Correos answered `{"pdf": null, "error": "El envío no existe"}`.
$pdf = base64_decode($labels->pdf);
```

The check covers the top-level `error`/`errors` field of every response. Nested errors stay on
the DTO, because there they are the answer rather than a failure: `validateShipments()` still
returns its per-shipment `validationErrorCount` and `error` list without throwing.

The raw response of the last call — including a failed one — is available on the resource:

```php
$correos->labels()->lastResponse()?->body();
```

## Retries

The API gateway rate limits, so transient failures are retried three times with exponential
backoff, starting at 500 ms:

```env
CORREOS_RETRY_TIMES=3
CORREOS_RETRY_INTERVAL=500
CORREOS_RETRY_EXPONENTIAL_BACKOFF=true
```

Set `CORREOS_RETRY_TIMES=1` to switch retries off.

What is retried is deliberately narrow, because a retried write can book the same shipment
twice:

| Failure | Read (`GET`) | Write (`POST`) |
| --- | --- | --- |
| `429 Too Many Requests` | retried | retried — the gateway rejected it before Correos saw it |
| `408`, `5xx` | retried | **not** retried |
| Connection error, timeout | retried | **not** retried |
| Any other `4xx` | not retried | not retried |

A write that fails on a timeout or a gateway error may well have been processed, so it is
surfaced to you instead of being repeated. See [Idempotency](#idempotency) for how to settle
one.

## Idempotency

`createShipments()` is not idempotent: a request that times out after Correos has registered
the shipment leaves you unable to tell success from failure, and sending it again books a
duplicate. Guard it in your own service layer:

1. Give every package a stable reference of your own (`clientReference` on `PackageData`) and
   store it, with the resulting shipment and package codes, against your order.
2. Before creating, skip orders that already carry a shipment code.
3. After a timeout or a `5xx`, reconcile rather than retry — ask Correos what it holds under
   that reference:

```php
$packages = $correos->preregister()->getPackagesByReference('ORDER-10231');

if ($packages->packageCodes) {
    // Already registered: store the codes instead of creating the shipment again.
}
```

## User agent

Requests identify the SDK and its installed version (`SmartDato-CorreosShippingSDK/1.2.3`).
Override it if Correos asks you to identify your own application:

```env
CORREOS_USER_AGENT="LaAnonima/2.1"
```

## Using it from Filament (or any Livewire component)

Nothing special is needed to call the SDK from a Filament page or action — but four things
are worth knowing.

**Strip nulls before hydrating a DTO.** Optional fields are typed `string|Optional`, and a
Filament form submits `null` for the ones the user left alone, which is a `TypeError` rather
than a validation error:

```php
$clean = fn (array $values) => collect($values)
    ->map(fn ($value) => is_array($value) ? $clean($value) : $value)
    ->reject(fn ($value) => $value === null || $value === '' || $value === [])
    ->all();

$request = DeliveryRequestData::from($clean($this->form->getState()));
```

**Keep writes off the request cycle.** `createShipments()` is not idempotent and is not
retried on transport failures, so run it from a queued job and report back with a
notification. If you do call the API inline, lower the timeouts for that path — the defaults
(30s per attempt, three attempts on reads) are sized for a worker, not for someone watching a
spinner:

```env
CORREOS_TIMEOUT=8
CORREOS_CONNECT_TIMEOUT=3
```

**Serve the PDF from the action.** `decodedPdf()` gives you the bytes directly:

```php
Action::make('label')
    ->action(fn (Shipment $record) => response()->streamDownload(
        fn () => print $correos->labels()->printLabels($record->labelRequest())->decodedPdf(),
        "etiqueta-{$record->shipment_code}.pdf",
    ));
```

Catching the failure is one `try`, and `errorCode` / `moreInformation` make a better
notification body than the raw message, which falls back to the response body when Correos
answers without one:

```php
} catch (CorreosApiException $e) {
    Notification::make()
        ->danger()
        ->title(__('The label could not be printed'))
        ->body($e->moreInformation ?? $e->errorCode)
        ->send();
}
```

**Selects and DTO properties.** `Enum::options()` feeds `Select::make(...)->options(...)`
straight; note PHP turns numeric string values into integer keys, so cast back when
hydrating a string-backed enum from form state (`ShipmentType::from((string) $state)`). And if
you want to hold a DTO in a public component property, turn on spatie's Livewire
synthesizers — they ship disabled:

```php
// config/data.php
'livewire' => [
    'enable_synths' => true,
],
```

## Testing

```bash
composer test             # Run tests
composer analyse          # Static analysis (PHPStan level 5)
composer format           # Code style (Laravel Pint)
composer test-coverage    # Tests with coverage report
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [SmartDato](https://github.com/smart-dato)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
