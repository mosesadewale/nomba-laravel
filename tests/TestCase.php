<?php

declare(strict_types=1);

namespace Nomba\Laravel\Tests;

use Nomba\Laravel\Facades\Nomba;
use Nomba\Laravel\NombaServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [NombaServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Nomba' => Nomba::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('nomba.client_id', 'test-id');
        $app['config']->set('nomba.client_secret', 'test-secret');
        $app['config']->set('nomba.account_id', 'test-account');
    }
}
