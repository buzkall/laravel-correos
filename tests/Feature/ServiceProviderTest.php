<?php

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
