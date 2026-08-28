<?php

/**
 * Sandbox integration tests against the Correos PRE environment.
 *
 * These tests make REAL API calls and require:
 * - A .env.testing file with PRE sandbox credentials
 * - IP whitelisted by Correos for api1.correospre.es
 * - PRE environment available (Mon-Fri 8:00-20:00 CET)
 *
 * Run manually with: vendor/bin/pest --group=sandbox
 */

use Dotenv\Dotenv;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SmartDato\CorreosShipping\Auth\CorreosAuthenticator;
use SmartDato\CorreosShipping\Connectors\PreregisterConnector;
use SmartDato\CorreosShipping\Data\Preregister\DeliveryRequestData;
use SmartDato\CorreosShipping\Data\Preregister\DeliveryResponseData;
use SmartDato\CorreosShipping\Requests\Preregister\ValidateShipmentsRequest;
use SmartDato\CorreosShipping\Resources\PreregisterResource;

function sandboxAvailable(): bool
{
    return file_exists(dirname(__DIR__, 2).'/.env.testing');
}

function loadSandboxEnv(): void
{
    static $loaded = false;
    if (! $loaded) {
        Dotenv::createMutable(dirname(__DIR__, 2), '.env.testing')->load();
        $loaded = true;
    }
}

/**
 * Read a required value from .env.testing as a string.
 */
function sandboxEnv(string $key, ?string $default = null): string
{
    $value = env($key, $default);

    if (! is_string($value) || $value === '') {
        throw new RuntimeException("The sandbox environment variable {$key} is not set in .env.testing.");
    }

    return $value;
}

function sandboxPreregisterConnector(): PreregisterConnector
{
    loadSandboxEnv();

    // Disable SSL for Laravel HTTP client (used by CorreosAuthenticator for token fetch)
    // PRE sandbox uses self-signed certificates
    Http::globalOptions(['verify' => false]);

    $auth = new CorreosAuthenticator(
        oauthClientId: sandboxEnv('CORREOS_OAUTH_CLIENT_ID'),
        oauthClientSecret: sandboxEnv('CORREOS_OAUTH_CLIENT_SECRET'),
        tokenUrl: sandboxEnv('CORREOS_TOKEN_URL'),
        scope: sandboxEnv('CORREOS_OAUTH_SCOPE', 'AP3 LBS RCG'),
        gatewayClientId: sandboxEnv('CORREOS_GATEWAY_CLIENT_ID'),
        gatewayClientSecret: sandboxEnv('CORREOS_GATEWAY_CLIENT_SECRET'),
        verifySsl: false,
    );

    $connector = new PreregisterConnector($auth, sandboxEnv('CORREOS_PREREGISTER_URL'));

    // Disable SSL for Saloon connector (Guzzle config)
    // Force IPv4 — PRE sandbox only whitelists IPv4, IPv6 gets 403 from CloudFront
    $connector->config()->add('verify', false);
    $connector->config()->add('force_ip_resolve', 'v4');

    return $connector;
}

function sandboxShipmentRequest(): DeliveryRequestData
{
    return DeliveryRequestData::from([
        'shipments' => [
            [
                'product' => 'PAFXB',
                'deliveryMethod' => 'DOUAOF',
                'contractNumber' => env('CORREOS_CONTRACT_NUMBER', '08000098'),
                'clientNumber' => env('CORREOS_CLIENT_NUMBER', 'CT08011405'),
                'labellerCode' => env('CORREOS_LABELLER_CODE', 'XXX1'),
                'packagesNumber' => '1',
                'sender' => [
                    'name' => 'OMEST SRL',
                    'address' => 'Via Roma 10',
                    'locality' => 'Madrid',
                    'province' => '28',
                    'cp' => '28001',
                    'country' => 'ESP',
                    'contactPhone' => '600000000',
                    'email' => 'test@example.com',
                    'language' => 'spa',
                ],
                'addressee' => [
                    'name' => 'Test Destinatario',
                    'address' => 'Calle Gran Via 1',
                    'locality' => 'Barcelona',
                    'province' => '08',
                    'cp' => '08001',
                    'country' => 'ESP',
                    'contactPhone' => '600000001',
                    'email' => 'dest@example.com',
                    'language' => 'spa',
                ],
                'packages' => [
                    [
                        'packageWeightGrams' => '500',
                    ],
                ],
            ],
        ],
    ]);
}

it('can obtain an OAuth token from the PRE sandbox', function (): void {
    Cache::flush();
    loadSandboxEnv();

    Http::globalOptions(['verify' => false]);

    $tokenResponse = Http::asForm()->post(sandboxEnv('CORREOS_TOKEN_URL'), [
        'grant_type' => 'client_credentials',
        'client_id' => sandboxEnv('CORREOS_OAUTH_CLIENT_ID'),
        'client_secret' => sandboxEnv('CORREOS_OAUTH_CLIENT_SECRET'),
        'scope' => sandboxEnv('CORREOS_OAUTH_SCOPE', 'AP3 LBS RCG'),
    ]);

    dump('Token HTTP status:', $tokenResponse->status());
    dump('Token response:', $tokenResponse->json() ?? $tokenResponse->body());

    expect($tokenResponse->successful())->toBeTrue()
        ->and($tokenResponse->json('idToken'))->not->toBeEmpty();
})->group('sandbox')
    ->skip(fn (): bool => ! sandboxAvailable(), 'Skipped: .env.testing not found');

it('can validate a shipment against the PRE sandbox', function (): void {
    Cache::flush();

    $connector = sandboxPreregisterConnector();

    $rawResponse = $connector->send(
        new ValidateShipmentsRequest(sandboxShipmentRequest())
    );

    dump('HTTP status:', $rawResponse->status());
    dump('Response body:', $rawResponse->body());

    expect($rawResponse->successful())->toBeTrue();

    $response = $rawResponse->dtoOrFail();
    expect($response)->toBeInstanceOf(DeliveryResponseData::class);

    dump('Validate response:', $response->toArray());
})->group('sandbox')
    ->skip(fn (): bool => ! sandboxAvailable(), 'Skipped: .env.testing not found');

it('can create a shipment against the PRE sandbox', function (): void {
    Cache::flush();

    $connector = sandboxPreregisterConnector();
    $resource = new PreregisterResource($connector);

    $response = $resource->createShipments(sandboxShipmentRequest());

    expect($response->shipments)->toBeArray()
        ->and($response->shipments)->not->toBeEmpty();

    dump('Create response:', $response->toArray());
    dump('Shipment code:', $response->shipments[0]->shipmentCode ?? 'N/A');
    dump('Raw HTTP status:', $resource->lastResponse()?->status());
})->group('sandbox')
    ->skip(fn (): bool => ! sandboxAvailable(), 'Skipped: .env.testing not found');
