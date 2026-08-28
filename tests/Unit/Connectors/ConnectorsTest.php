<?php

use SmartDato\CorreosShipping\Auth\CorreosAuthenticator;
use SmartDato\CorreosShipping\Connectors\LabelsConnector;
use SmartDato\CorreosShipping\Connectors\PreregisterConnector;
use SmartDato\CorreosShipping\Connectors\TrackingConnector;

function makeAuthenticator(): CorreosAuthenticator
{
    return new CorreosAuthenticator(
        oauthClientId: 'oauth-id',
        oauthClientSecret: 'oauth-secret',
        tokenUrl: 'https://example.com/token',
        scope: 'AP3 LBS RCG',
        gatewayClientId: 'gateway-id',
        gatewayClientSecret: 'gateway-secret',
    );
}

it('preregister connector resolves correct base url', function () {
    config()->set('correos-shipping-sdk.base_urls.preregister', 'https://api1.correos.es/admissions/preregister/api/v1');

    $connector = new PreregisterConnector(makeAuthenticator());

    expect($connector->resolveBaseUrl())->toBe('https://api1.correos.es/admissions/preregister/api/v1');
});

it('labels connector resolves correct base url', function () {
    config()->set('correos-shipping-sdk.base_urls.labels', 'https://api1.correos.es/support/labels/api/v1');

    $connector = new LabelsConnector(makeAuthenticator());

    expect($connector->resolveBaseUrl())->toBe('https://api1.correos.es/support/labels/api/v1');
});

it('tracking connector resolves correct base url', function () {
    config()->set('correos-shipping-sdk.base_urls.tracking', 'https://api1.correos.es/support/trackpub/api/v2');

    $connector = new TrackingConnector(makeAuthenticator());

    expect($connector->resolveBaseUrl())->toBe('https://api1.correos.es/support/trackpub/api/v2');
});

it('connectors set default json headers', function () {
    $connector = new PreregisterConnector(makeAuthenticator());

    $headers = $connector->headers()->all();

    expect($headers)->toHaveKey('Accept', 'application/json')
        ->toHaveKey('Content-Type', 'application/json');
});

it('connectors set force_ip_resolve in config when provided', function () {
    $connector = new PreregisterConnector(makeAuthenticator(), forceIpResolve: 'v4');

    $config = $connector->config()->all();

    expect($config)->toHaveKey('force_ip_resolve', 'v4');
});

it('connectors do not set force_ip_resolve when null', function () {
    $connector = new PreregisterConnector(makeAuthenticator());

    $config = $connector->config()->all();

    expect($config)->not->toHaveKey('force_ip_resolve');
});

it('connectors identify the sdk in the user agent', function () {
    $connector = new PreregisterConnector(makeAuthenticator());

    expect($connector->headers()->get('User-Agent'))->toStartWith('SmartDato-CorreosShippingSDK');
});

it('connectors accept a custom user agent', function () {
    $connector = new PreregisterConnector(makeAuthenticator(), userAgent: 'LaAnonima/2.1');

    expect($connector->headers()->get('User-Agent'))->toBe('LaAnonima/2.1');
});

it('connectors retry three times with exponential backoff by default', function () {
    $connector = new PreregisterConnector(makeAuthenticator());

    expect($connector->tries)->toBe(3)
        ->and($connector->retryInterval)->toBe(500)
        ->and($connector->useExponentialBackoff)->toBeTrue();
});

it('connectors resolved from the container follow the retry config', function () {
    config()->set('correos-shipping-sdk.retry.times', 5);
    config()->set('correos-shipping-sdk.retry.interval', 100);
    config()->set('correos-shipping-sdk.retry.exponential_backoff', false);
    config()->set('correos-shipping-sdk.user_agent', 'LaAnonima/1.0');

    $connector = app(PreregisterConnector::class);

    expect($connector->tries)->toBe(5)
        ->and($connector->retryInterval)->toBe(100)
        ->and($connector->useExponentialBackoff)->toBeFalse()
        ->and($connector->headers()->get('User-Agent'))->toBe('LaAnonima/1.0');
});
