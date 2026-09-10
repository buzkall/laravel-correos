<?php

namespace Arzcode\LaravelCorreos\Connectors;

class LabelsConnector extends CorreosConnector
{
    public function resolveBaseUrl(): string
    {
        return $this->baseUrl ?? config('laravel-correos.base_urls.labels');
    }
}
