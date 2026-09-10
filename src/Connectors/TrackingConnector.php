<?php

namespace Arzcode\LaravelCorreos\Connectors;

class TrackingConnector extends CorreosConnector
{
    public function resolveBaseUrl(): string
    {
        return $this->baseUrl ?? config('laravel-correos.base_urls.tracking');
    }
}
