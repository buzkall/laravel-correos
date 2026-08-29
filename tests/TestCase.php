<?php

namespace SmartDato\CorreosShipping\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SmartDato\CorreosShipping\CorreosShippingServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelDataServiceProvider::class,
            CorreosShippingServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
    }
}
