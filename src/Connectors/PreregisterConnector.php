<?php

namespace Arzcode\LaravelCorreos\Connectors;

class PreregisterConnector extends CorreosConnector
{
    public function resolveBaseUrl(): string
    {
        return $this->baseUrl ?? config('laravel-correos.base_urls.preregister');
    }
}
