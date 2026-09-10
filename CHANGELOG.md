# Changelog

All notable changes to `laravel-correos` will be documented in this file.

## 0.1.0 - 2026-09-10

First release under the `arzcode/laravel-correos` name. The package started as a fork of
[smart-dato/correos-shipping-sdk](https://github.com/smart-dato/correos-shipping-sdk) at 0.0.4 and has
diverged since; earlier version numbers belong to the upstream package.

### Upgrading from `smart-dato/correos-shipping-sdk`

- The namespace is now `Arzcode\LaravelCorreos`, the entry point is `Correos` (`Correos::make()` and the
  `Correos` facade) and the config file is `config/laravel-correos.php`. Rename imports, republish the
  config and move any `correos-shipping-sdk.*` config overrides to `laravel-correos.*`.
- PHP 8.4 and Laravel 13 are required.

### Changed

- Responses that come back as HTTP 200 with a filled top-level `error` or `errors` field now throw
  `CorreosApiException`, the same as an error status would. Printing a label for an unknown shipment used
  to return a DTO with a null PDF and no exception. Per-shipment validation errors from `validateShipments()`
  stay on the DTO.
- `CorreosApiException` extends Saloon's `RequestException`, so the failed response is reachable through
  `getResponse()`.
- Transient gateway failures are retried three times with exponential backoff, configurable through the
  `retry` config block or the `make()` constructor. A 429 is always retried. Connection errors, timeouts and
  5xx responses are only retried for reads, so a shipment is never booked twice; a write that may have been
  processed is surfaced to the caller to reconcile.
- A token rejected with a 401 is dropped from the cache and the request is retried once with a fresh one,
  instead of failing until the cached token aged out.
- The token endpoint answering without an `idToken` now raises a clear exception instead of a `TypeError`.
- The `User-Agent` header reports the installed package version instead of a hardcoded `1.0`, and can be
  overridden with the `user_agent` config key.
- Request and connect timeouts are configurable per environment through the `timeout` and
  `connect_timeout` config keys. Left unset they keep Saloon's defaults.
- The SDK is registered as a scoped binding rather than a singleton, so `lastResponse()` no longer hands
  back a previous request's data under Octane or in long-running queue workers.
- A downstream connector can now override the package name and user agent.

### Added

- `decodedPdf()` on `LabelsResponseData` and `DocumentResponseData` returns the decoded PDF bytes, or null
  when the response carries no usable PDF.
- Every enum has a `label()` and a static `options()` for feeding select inputs.
- The README documents error handling, retries, idempotency, the user agent, IP whitelisting on PRE,
  composing labels onto an A4 sheet, and how to call the SDK from a Livewire or Filament component.

### Fixed

- Shipment and expedition codes are URL-encoded before being placed in the endpoint path, so user input
  cannot reshape the request URL.
- A query parameter with the value `'0'` is sent instead of being silently dropped.
