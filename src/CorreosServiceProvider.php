<?php

namespace Arzcode\LaravelCorreos;

use Arzcode\LaravelCorreos\Auth\CorreosAuthenticator;
use Arzcode\LaravelCorreos\Connectors\CorreosConnector;
use Arzcode\LaravelCorreos\Connectors\LabelsConnector;
use Arzcode\LaravelCorreos\Connectors\PreregisterConnector;
use Arzcode\LaravelCorreos\Connectors\TrackingConnector;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CorreosServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-correos')
            ->hasConfigFile();
    }

    /**
     * Timeouts are optional: an unset config key leaves Saloon's default in
     * place rather than sending a zero.
     */
    protected function optionalInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(CorreosAuthenticator::class, fn (): CorreosAuthenticator => new CorreosAuthenticator(
            oauthClientId: (string) config('laravel-correos.oauth.client_id'),
            oauthClientSecret: (string) config('laravel-correos.oauth.client_secret'),
            tokenUrl: (string) config('laravel-correos.oauth.token_url'),
            scope: (string) config('laravel-correos.oauth.scope'),
            gatewayClientId: (string) config('laravel-correos.gateway.client_id'),
            gatewayClientSecret: (string) config('laravel-correos.gateway.client_secret'),
            verifySsl: (bool) config('laravel-correos.verify_ssl', true),
            forceIpResolve: config('laravel-correos.force_ip_resolve'),
        ));

        foreach ([PreregisterConnector::class, LabelsConnector::class, TrackingConnector::class] as $connector) {
            $this->app->singleton($connector, fn ($app): CorreosConnector => new $connector(
                $app->make(CorreosAuthenticator::class),
                verifySsl: (bool) config('laravel-correos.verify_ssl', true),
                forceIpResolve: config('laravel-correos.force_ip_resolve'),
                tries: (int) config('laravel-correos.retry.times', 3),
                retryInterval: (int) config('laravel-correos.retry.interval', 500),
                useExponentialBackoff: (bool) config('laravel-correos.retry.exponential_backoff', true),
                userAgent: config('laravel-correos.user_agent'),
                timeout: $this->optionalInt(config('laravel-correos.timeout')),
                connectTimeout: $this->optionalInt(config('laravel-correos.connect_timeout')),
            ));
        }

        $this->app->singleton(Correos::class, fn ($app): Correos => new Correos(
            $app->make(PreregisterConnector::class),
            $app->make(LabelsConnector::class),
            $app->make(TrackingConnector::class),
        ));
    }
}
