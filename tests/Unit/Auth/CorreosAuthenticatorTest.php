<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SmartDato\CorreosShipping\Auth\CorreosAuthenticator;
use SmartDato\CorreosShipping\Connectors\LabelsConnector;
use SmartDato\CorreosShipping\Data\Labels\PrintLabelsRequestData;
use SmartDato\CorreosShipping\Requests\Labels\PrintLabelsRequest;
use SmartDato\CorreosShipping\Tests\Doubles\TestableCorreosAuthenticator;

/**
 * @param  array<string, mixed>  $claims
 */
function fakeCorreosJwt(array $claims): string
{
    $encode = fn (array $data): string => rtrim(strtr(base64_encode(json_encode($data, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');

    return $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode($claims).'.signature';
}

function testableAuthenticator(): TestableCorreosAuthenticator
{
    return new TestableCorreosAuthenticator(
        oauthClientId: 'oauth-id',
        oauthClientSecret: 'oauth-secret',
        tokenUrl: 'https://example.com/token',
        scope: 'AP3 LBS RCG',
        gatewayClientId: 'gateway-id',
        gatewayClientSecret: 'gateway-secret',
    );
}

it('sets authorization and gateway headers on pending request', function (): void {
    $authenticator = new CorreosAuthenticator(
        oauthClientId: 'oauth-id',
        oauthClientSecret: 'oauth-secret',
        tokenUrl: 'https://example.com/token',
        scope: 'AP3 LBS RCG',
        gatewayClientId: 'gateway-id',
        gatewayClientSecret: 'gateway-secret',
    );

    Cache::put($authenticator->cacheKey(), 'fake-test-token', 3600);
    config()->set('correos-shipping-sdk.base_urls.labels', 'https://api1.correos.es/support/labels/api/v1');

    $request = new PrintLabelsRequest(PrintLabelsRequestData::from([
        'documentationType' => 1,
        'print' => [
            'shipments' => ['PQXYZ1234567890'],
            'labelFormat' => 2,
            'labelPrintMode' => 1,
        ],
    ]));

    $headers = new LabelsConnector($authenticator)->createPendingRequest($request)->headers()->all();

    expect($headers)->toHaveKey('Authorization', 'Bearer fake-test-token')
        ->toHaveKey('client_id', 'gateway-id')
        ->toHaveKey('client_secret', 'gateway-secret');
});

it('derives the cache ttl from the jwt exp claim minus a safety buffer', function (): void {
    $token = fakeCorreosJwt(['exp' => time() + 1800]);

    $ttl = testableAuthenticator()->exposedGetTokenTtl($token);

    expect($ttl)->toBeGreaterThan(1730)
        ->and($ttl)->toBeLessThanOrEqual(1740);
});

it('returns a zero ttl for an already expired token', function (): void {
    $token = fakeCorreosJwt(['exp' => time() - 10]);

    expect(testableAuthenticator()->exposedGetTokenTtl($token))->toBe(0);
});

it('falls back to a conservative ttl when the token is not a parseable jwt', function (): void {
    expect(testableAuthenticator()->exposedGetTokenTtl('opaque-token'))->toBe(1500);
});

it('falls back to a conservative ttl when the jwt has no exp claim', function (): void {
    $token = fakeCorreosJwt(['iss' => 'CID']);

    expect(testableAuthenticator()->exposedGetTokenTtl($token))->toBe(1500);
});

it('caches the fetched token and reuses it on subsequent calls', function (): void {
    Cache::flush();

    $token = fakeCorreosJwt(['exp' => time() + 1800]);

    Http::fake([
        'example.com/token' => Http::response([
            'idToken' => $token,
            'tokenType' => 'Bearer',
            'expiresIn' => 30,
        ]),
    ]);

    $authenticator = testableAuthenticator();

    expect($authenticator->exposedGetToken())->toBe($token)
        ->and($authenticator->exposedGetToken())->toBe($token)
        ->and(Cache::get($authenticator->cacheKey()))->toBe($token);

    Http::assertSentCount(1);
});

it('caches tokens per account', function (): void {
    $accountOne = new CorreosAuthenticator('id-one', 'secret-one', 'https://example.com/token', 'AP3', 'gw-id', 'gw-secret');
    $accountTwo = new CorreosAuthenticator('id-two', 'secret-two', 'https://example.com/token', 'AP3', 'gw-id', 'gw-secret');
    $accountOneOnPre = new CorreosAuthenticator('id-one', 'secret-one', 'https://pre.example.com/token', 'AP3', 'gw-id', 'gw-secret');

    expect($accountOne->cacheKey())->not->toBe($accountTwo->cacheKey())
        ->and($accountOne->cacheKey())->not->toBe($accountOneOnPre->cacheKey());
});

it('does not cache a token that is about to expire', function (): void {
    Cache::flush();

    $token = fakeCorreosJwt(['exp' => time() + 30]);

    Http::fake([
        'example.com/token' => Http::response([
            'idToken' => $token,
            'tokenType' => 'Bearer',
            'expiresIn' => 30,
        ]),
    ]);

    $authenticator = testableAuthenticator();

    expect($authenticator->exposedGetToken())->toBe($token)
        ->and(Cache::get($authenticator->cacheKey()))->toBeNull();
});
