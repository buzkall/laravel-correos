<?php

namespace SmartDato\CorreosShipping\Connectors;

use Composer\InstalledVersions;
use Saloon\Contracts\Authenticator;
use Saloon\Enums\Method;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Http\Response;
use SmartDato\CorreosShipping\Auth\CorreosAuthenticator;
use SmartDato\CorreosShipping\Exceptions\CorreosApiException;
use Throwable;

abstract class CorreosConnector extends Connector
{
    protected const PACKAGE_NAME = 'smart-dato/correos-shipping-sdk';

    protected const DEFAULT_TRIES = 3;

    protected const DEFAULT_RETRY_INTERVAL = 500;

    public function __construct(
        protected CorreosAuthenticator $correosAuthenticator,
        protected ?string $baseUrl = null,
        protected bool $verifySsl = true,
        protected ?string $forceIpResolve = null,
        ?int $tries = null,
        ?int $retryInterval = null,
        ?bool $useExponentialBackoff = null,
        protected ?string $userAgent = null,
        protected ?int $timeout = null,
        protected ?int $connectTimeout = null,
    ) {
        $this->tries = $tries ?? self::DEFAULT_TRIES;
        $this->retryInterval = $retryInterval ?? self::DEFAULT_RETRY_INTERVAL;
        $this->useExponentialBackoff = $useExponentialBackoff ?? true;
    }

    protected function defaultConfig(): array
    {
        $config = [
            'verify' => $this->verifySsl,
        ];

        if ($this->forceIpResolve) {
            $config['force_ip_resolve'] = $this->forceIpResolve;
        }

        // Left unset, Saloon's own defaults apply (30s request, 10s connect).
        if ($this->timeout !== null) {
            $config['timeout'] = $this->timeout;
        }

        if ($this->connectTimeout !== null) {
            $config['connect_timeout'] = $this->connectTimeout;
        }

        return $config;
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => $this->userAgent ?? self::defaultUserAgent(),
        ];
    }

    protected function defaultAuth(): ?Authenticator
    {
        return $this->correosAuthenticator;
    }

    public function getRequestException(Response $response, ?Throwable $senderException): ?Throwable
    {
        return CorreosApiException::fromResponse($response);
    }

    /**
     * The API gateway rate limits and answers with the occasional gateway
     * error, so transient failures are retried. Nothing that could have
     * reached the shipment register is: a POST that timed out or died on a
     * gateway error may well have been processed, and retrying it would book
     * the same shipment twice. Those calls are reconciled instead — see
     * `getPackagesByReference()`.
     */
    public function handleRetry(FatalRequestException|RequestException $exception, Request $request): bool
    {
        // Rate limiting means the request was rejected before it was processed,
        // so it is always safe to send again.
        if ($exception instanceof RequestException && $exception->getResponse()->status() === 429) {
            return true;
        }

        if (! $this->isIdempotent($request)) {
            return false;
        }

        // The API never answered: a connection reset, a DNS failure, a timeout.
        if ($exception instanceof FatalRequestException) {
            return true;
        }

        $status = $exception->getResponse()->status();

        return $status === 408 || $status >= 500;
    }

    protected function isIdempotent(Request $request): bool
    {
        return in_array($request->getMethod(), [Method::GET, Method::HEAD, Method::OPTIONS], true);
    }

    /**
     * Identifies the SDK and the version actually installed, rather than a
     * version string that has to be remembered on every release.
     */
    protected static function defaultUserAgent(): string
    {
        $version = InstalledVersions::isInstalled(self::PACKAGE_NAME)
            ? InstalledVersions::getPrettyVersion(self::PACKAGE_NAME)
            : null;

        return 'SmartDato-CorreosShippingSDK'.($version !== null ? '/'.$version : '');
    }
}
