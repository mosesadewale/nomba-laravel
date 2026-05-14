<?php

declare(strict_types=1);

namespace Nomba\Laravel;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\ServiceProvider;
use Nomba\Sdk\Contracts\NombaClientInterface;
use Nomba\Sdk\Enums\Environment;
use Nomba\Sdk\Factory;
use Nomba\Sdk\NombaClient;

class NombaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/nomba.php', 'nomba');

        $this->app->singleton(NombaClient::class, function ($app): NombaClient {
            $config = $app['config']['nomba'];

            $cache = $app->make(CacheRepository::class);

            return Factory::make(
                clientId:      $config['client_id'],
                clientSecret:  $config['client_secret'],
                accountId:     $config['account_id'],
                environment:   Environment::from($config['environment']),
                timeout:       (float) $config['timeout'],
                retryAttempts: (int)   $config['retry_attempts'],
                webhookSecret: $config['webhook_secret'],
                cache:         $cache,
            );
        });

        $this->app->alias(NombaClient::class, NombaClientInterface::class);
    }

    public function boot(): void
    {
        if (config('nomba.register_webhook_route', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/webhooks.php');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/nomba.php' => config_path('nomba.php'),
            ], 'nomba-config');
        }
    }
}
