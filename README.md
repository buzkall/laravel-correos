# Correos Shipping SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/arzcode/laravel-correos.svg?style=flat-square)](https://packagist.org/packages/arzcode/laravel-correos)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/buzkall/laravel-correos/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/buzkall/laravel-correos/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/buzkall/laravel-correos/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/buzkall/laravel-correos/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/arzcode/laravel-correos.svg?style=flat-square)](https://packagist.org/packages/arzcode/laravel-correos)

Laravel package for the Correos (Spanish postal service) APIs: shipment preregistration,
label and customs document generation, and tracking. Built on [Saloon 4.x](https://docs.saloon.dev)
for HTTP and [Spatie Laravel Data 4.x](https://spatie.be/docs/laravel-data) for DTOs.

Requires **PHP 8.4+** and **Laravel 11, 12 or 13**.

> Collections (*recogidas*) are not covered: the API has no resource for them yet, so
> shipments are handed over at an office or picked up under a standing agreement.

## Installation

```bash
composer require arzcode/laravel-correos
php artisan vendor:publish --tag="laravel-correos-config"
```

Add your credentials to `.env`:

```env
CORREOS_OAUTH_CLIENT_ID=your-oauth-client-id
CORREOS_OAUTH_CLIENT_SECRET=your-oauth-client-secret
CORREOS_GATEWAY_CLIENT_ID=your-gateway-client-id
CORREOS_GATEWAY_CLIENT_SECRET=your-gateway-client-secret
```

That is all production needs. Everything else in `config/laravel-correos.php` has a
working default — see [Configuration reference](#configuration-reference) for the rest.

## Usage

Resolve the SDK from the container:

```php
use Arzcode\LaravelCorreos\Correos;

$correos = app(Correos::class);
$correos->preregister()->createShipments($request);
```

Or reach the same instance through the facade:

```php
use Arzcode\LaravelCorreos\Facades\Correos;

Correos::preregister()->createShipments($request);
```

Outside Laravel, or with runtime credentials, build one by hand with
`Arzcode\LaravelCorreos\Correos::make(['oauth_client_id' => ..., 'gateway_client_id' => ..., ...])`.

### Preregister a shipment

```php
use Arzcode\LaravelCorreos\Data\Preregister\DeliveryRequestData;

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

$correos->preregister()->validateShipments($request);   // dry run, no shipment created
$response = $correos->preregister()->createShipments($request);

$response->fileIdentifier;                            // "FILE001"
$response->shipments[0]->shipmentCode;                // "PQXYZ1234567890"
$response->shipments[0]->packages[0]->packageCode;    // "PQ1DR4A0000012345678"
```

### Print labels

```php
use Arzcode\LaravelCorreos\Data\Labels\PrintLabelsRequestData;

$labels = $correos->labels()->printLabels(PrintLabelsRequestData::from([
    'documentationType' => 1, // 0=All, 1=Label, 2=CN22/CN23
    'print' => [
        'shipments' => ['PQXYZ1234567890'],
        'labelFormat' => 2,    // 1=XML, 2=PDF, 3=ZPL
        'labelPrintMode' => 1, // 1=A4, 2=Labeler
    ],
]));

$labels->pdf;            // Base64-encoded PDF content
$labels->decodedPdf();   // The same PDF as raw bytes, or null if there is none
```

`labelPrintMode` decides what that PDF contains, and the two modes are not interchangeable:
`1` (A4) returns a full page with the labels already laid out on the sheet, `2` (labeler)
returns one label per page at label size.

<details>
<summary>Composing your own A4 sheet with FPDI</summary>

To place labels yourself — starting at an arbitrary cell, or mixing carriers on one sheet —
ask for mode `2` and compose the page; mode `1` gives you a sheet you would have to cut up
again:

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

</details>

### Print customs documents (DCAF/DDP)

```php
use Arzcode\LaravelCorreos\Data\Labels\PrintDocumentsRequestData;

$document = $correos->labels()->printDocuments(PrintDocumentsRequestData::from([
    'documentationType' => 5, // 5=DCAF, 6=DDP
    'documentData' => [
        'destinationName' => 'France',
        'contractNumber' => '12345678',
        'clientNumber' => '1234567890',
    ],
]));

$document->pdf;  // Base64-encoded PDF
```

### Track a shipment

```php
$tracking = $correos->tracking()->searchShipment('PQ1DR4A0000012345678');

$tracking->code;          // "PQ1DR4A0000012345678"
$tracking->codProduct;    // "PQDOM"
$tracking->remitName;     // Sender name
$tracking->destiName;     // Addressee name

foreach ($tracking->events as $event) {
    $event->eventDate;     // "06/02/2026"
    $event->eventCode;     // "P010000V"
    $event->summaryText;   // "Shipment preregistered"
    $event->location;      // "CTA MADRID"
}
```

### All available methods

Every method takes and returns typed DTOs from `Arzcode\LaravelCorreos\Data\*`.

**`$correos->preregister()`**

| Method | Purpose |
| --- | --- |
| `validateShipments(DeliveryRequestData)` | Validate without creating |
| `createShipments(DeliveryRequestData)` | Create shipments |
| `createCnShipments(DeliveryRequestData)` | Create shipments with CN22/CN23 customs data |
| `modifyShipment(DeliveryRequestData)` | Modify an existing shipment |
| `cancelShipment(AnnulmentRequestData)` | Cancel a shipment |
| `cancelExpedition(AnnulmentExpeditionRequestData)` | Cancel a whole expedition |
| `generateShipmentCode(GenerateShipmentCodeRequestData)` | Reserve codes without preregistering |
| `queryShipments(QueryRequestData)` | Query shipments by code |
| `queryShipmentsIris(QueryRequestData)` | Same query against the IRIS backend |
| `getExpeditionPackages(string $expeditionCode)` | Packages of an expedition |
| `getPackagesByReference(string $clientReference, ?string $contractNumber, ?string $clientNumber)` | Look up by your own reference |
| `searchLabelsInfo(SearchLabelsInfoRequestData)` | Label metadata for a set of shipments |
| `getBackofficeShipment(string $shipmentCode)` | Backoffice detail for one shipment |
| `getBackofficeErrors(?$contractNumber, ?$clientNumber, ?$dateFrom, ?$dateTo)` | Shipments rejected by the backoffice |
| `getBackofficeTotal(?$contractNumber, ?$clientNumber, ?$dateFrom, ?$dateTo)` | Totals for a period |
| `getBackofficeWaiting(?$contractNumber, ?$clientNumber, ?$dateFrom, ?$dateTo)` | Shipments waiting for admission |

**`$correos->labels()`**

| Method | Purpose |
| --- | --- |
| `printLabels(PrintLabelsRequestData)` | Labels as PDF, XML or ZPL |
| `printDocuments(PrintDocumentsRequestData)` | Customs documents (DCAF/DDP) |
| `getDocumentBackoffice(string $shipment)` | Documents already generated for a shipment |

**`$correos->tracking()`**

| Method | Purpose |
| --- | --- |
| `searchShipment(string $shippingCode)` | Shipment status and event history |
| `getExpedition(string $expeditionCode)` | Expedition with its clients and packages |

### Enums

Typed enums cover the API's magic numbers. Each case carries a human readable `label()`, and
every enum exposes `options()` — value => label pairs, ready for a select input:

```php
use Arzcode\LaravelCorreos\Enums\ProductCode;    // PaqPremium, PaqEstandar, PaqToday, ...
use Arzcode\LaravelCorreos\Enums\LabelPrintMode; // A4, Labeler

ProductCode::PaqPremium->label();  // "Paq Premium"
LabelPrintMode::options();         // [1 => 'A4 sheet', 2 => 'Labeler']
```

The full set: `ProductCode`, `DocumentationType`, `LabelFormat`, `LabelPrintMode`,
`LabelOrderType`, `ShipmentType`, `DoiType`, `AdmissionMethod` and `ErrorCodeLanguage`.

## Error handling

API errors are thrown as `CorreosApiException`, which extends Saloon's `RequestException`:

```php
use Arzcode\LaravelCorreos\Exceptions\CorreosApiException;

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

Part of the Correos surface answers failures with HTTP 200 and an `error` field instead of an
error status — printing a label for an unknown shipment comes back as `200` with a null `pdf`
and a filled `error`. Those payloads raise the same exception, so a call that returns a DTO
has returned a usable one:

```php
$labels = $correos->labels()->printLabels($labelRequest);

// Never reached when Correos answered `{"pdf": null, "error": "El envío no existe"}`.
$pdf = $labels->decodedPdf();
```

The check covers the top-level `error`/`errors` field of every response. Nested errors stay on
the DTO, because there they are the answer rather than a failure: `validateShipments()` still
returns its per-shipment `validationErrorCount` and `error` list without throwing.

The raw response of the last call — including a failed one — stays on the resource:

```php
$correos->labels()->lastResponse()?->body();
```

## Retries and idempotency

The API gateway rate limits, so transient failures are retried three times with exponential
backoff starting at 500 ms. Set `CORREOS_RETRY_TIMES=1` to switch retries off.

What is retried is deliberately narrow, because a retried write can book the same shipment
twice:

| Failure | Read (`GET`) | Write (`POST`) |
| --- | --- | --- |
| `429 Too Many Requests` | retried | retried — the gateway rejected it before Correos saw it |
| `401 Unauthorized` | retried | retried — the cached token is dropped and a fresh one fetched |
| `408`, `5xx` | retried | **not** retried |
| Connection error, timeout | retried | **not** retried |
| Any other `4xx` | not retried | not retried |

<details>
<summary>Making <code>createShipments()</code> safe to repeat</summary>

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

</details>

## Configuration reference

<details>
<summary>Every environment variable</summary>

| Variable | Default | Purpose |
| --- | --- | --- |
| `CORREOS_OAUTH_CLIENT_ID` | — | CorreosID OAuth client id |
| `CORREOS_OAUTH_CLIENT_SECRET` | — | CorreosID OAuth client secret |
| `CORREOS_GATEWAY_CLIENT_ID` | — | API gateway client id |
| `CORREOS_GATEWAY_CLIENT_SECRET` | — | API gateway client secret |
| `CORREOS_TOKEN_URL` | `https://apioauthcid.correos.es/Api/Authorize/Token` | OAuth token endpoint |
| `CORREOS_OAUTH_SCOPE` | `AP3 LBS RCG` | Requested scopes |
| `CORREOS_PREREGISTER_URL` | `https://api1.correos.es/admissions/preregister/api/v1` | Preregister base URL |
| `CORREOS_LABELS_URL` | `https://api1.correos.es/support/labels/api/v1` | Labels base URL |
| `CORREOS_TRACKING_URL` | `https://api1.correos.es/support/trackpub/api/v2` | Tracking base URL |
| `CORREOS_VERIFY_SSL` | `true` | Verify TLS certificates |
| `CORREOS_FORCE_IP_RESOLVE` | — | `v4` to force IPv4 |
| `CORREOS_RETRY_TIMES` | `3` | Attempts per request |
| `CORREOS_RETRY_INTERVAL` | `500` | Milliseconds before the first retry |
| `CORREOS_RETRY_EXPONENTIAL_BACKOFF` | `true` | Double the interval each attempt |
| `CORREOS_TIMEOUT` | Saloon's 30s | Request timeout, seconds |
| `CORREOS_CONNECT_TIMEOUT` | Saloon's 10s | Connection timeout, seconds |
| `CORREOS_USER_AGENT` | `Arzcode-LaravelCorreos/1.2.3` | Override to identify your own app |

</details>

<details>
<summary>Pre-production environment</summary>

Override the four URLs:

```env
CORREOS_TOKEN_URL=https://apioauthcid.correospre.es/Api/Authorize/Token
CORREOS_PREREGISTER_URL=https://api1.correospre.es/admissions/preregister/api/v1
CORREOS_LABELS_URL=https://api1.correospre.es/support/labels/api/v1
CORREOS_TRACKING_URL=https://api1.correospre.es/support/trackpub/api/v2
```

PRE tends to use self-signed certificates and to answer on IPv4 only, so you may also need:

```env
CORREOS_VERIFY_SSL=false
CORREOS_FORCE_IP_RESOLVE=v4
```

> **Warning:** never disable SSL verification in production.

**Network access.** Correos whitelists the client IP for PRE: connections from a
non-whitelisted address (and any IPv6 address, which CloudFront answers with a `403`) are
rejected before they reach the API, and PRE is only up Monday to Friday, 08:00–20:00 CET.
Confirm with your Correos commercial contact whether your production contract carries the
same restriction; if it does, every host that calls the API — web servers, queue workers,
scheduled jobs — has to egress from a fixed, whitelisted IPv4 address, which usually means
pinning them to a static IP or routing them through a NAT gateway.

</details>

## Using it from Filament (or any Livewire component)

Nothing special is needed to call the SDK from a Filament page or action — but four things
are worth knowing.

<details>
<summary>Strip nulls before hydrating a DTO</summary>

Optional fields are typed `string|Optional`, and a Filament form submits `null` for the ones
the user left alone, which is a `TypeError` rather than a validation error:

```php
$clean = fn (array $values) => collect($values)
    ->map(fn ($value) => is_array($value) ? $clean($value) : $value)
    ->reject(fn ($value) => $value === null || $value === '' || $value === [])
    ->all();

$request = DeliveryRequestData::from($clean($this->form->getState()));
```

</details>

<details>
<summary>Keep writes off the request cycle</summary>

`createShipments()` is not idempotent and is not retried on transport failures, so run it
from a queued job and report back with a notification. If you do call the API inline, lower
the timeouts for that path — the defaults (30s per attempt, three attempts on reads) are
sized for a worker, not for someone watching a spinner:

```env
CORREOS_TIMEOUT=8
CORREOS_CONNECT_TIMEOUT=3
```

</details>

<details>
<summary>Serve the PDF from the action</summary>

`decodedPdf()` gives you the bytes directly:

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

</details>

<details>
<summary>Selects and DTO properties</summary>

`Enum::options()` feeds `Select::make(...)->options(...)` straight; note PHP turns numeric
string values into integer keys, so cast back when hydrating a string-backed enum from form
state (`ShipmentType::from((string) $state)`). And if you want to hold a DTO in a public
component property, turn on spatie's Livewire synthesizers — they ship disabled:

```php
// config/data.php
'livewire' => [
    'enable_synths' => true,
],
```

</details>

## Testing

```bash
composer test             # Run tests (Pest 5)
composer analyse          # Static analysis of src, config and tests (PHPStan level 7)
composer format           # Code style (Laravel Pint)
composer rector-dry       # Preview automated refactors (Rector)
composer rector           # Apply automated refactors (Rector)
composer test-coverage    # Tests with coverage report
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Pull requests are welcome. Run `composer test`, `composer analyse` and `composer format`
before opening one.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [arzcode](https://github.com/buzkall)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
