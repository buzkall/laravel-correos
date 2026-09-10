<?php

use Arzcode\LaravelCorreos\Connectors\LabelsConnector;
use Arzcode\LaravelCorreos\Connectors\PreregisterConnector;
use Arzcode\LaravelCorreos\Connectors\TrackingConnector;
use Arzcode\LaravelCorreos\Correos;

it('can resolve correos shipping from container', function (): void {
    config()->set('laravel-correos.oauth.client_id', 'test');
    config()->set('laravel-correos.oauth.client_secret', 'test');
    config()->set('laravel-correos.oauth.token_url', 'https://example.com/token');
    config()->set('laravel-correos.oauth.scope', 'AP3 LBS RCG');
    config()->set('laravel-correos.gateway.client_id', 'test');
    config()->set('laravel-correos.gateway.client_secret', 'test');

    // The service provider registers the SDK as a singleton, so the container
    // must hand back the very same instance on every resolution.
    expect(resolve(Correos::class))->toBe(resolve(Correos::class));
});

it('registers each connector against its own class', function (): void {
    config()->set('laravel-correos.base_urls.preregister', 'https://api1.correos.es/preregister');
    config()->set('laravel-correos.base_urls.labels', 'https://api1.correos.es/labels');
    config()->set('laravel-correos.base_urls.tracking', 'https://api1.correos.es/tracking');
    config()->set('laravel-correos.retry.times', 5);

    // Each binding has to build its own connector: they differ only in the
    // base url they resolve, so that is what tells them apart.
    expect(resolve(PreregisterConnector::class)->resolveBaseUrl())->toBe('https://api1.correos.es/preregister')
        ->and(resolve(LabelsConnector::class)->resolveBaseUrl())->toBe('https://api1.correos.es/labels')
        ->and(resolve(TrackingConnector::class)->resolveBaseUrl())->toBe('https://api1.correos.es/tracking')
        ->and(resolve(TrackingConnector::class)->tries)->toBe(5);
});
