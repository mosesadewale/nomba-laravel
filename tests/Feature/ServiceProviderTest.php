<?php

declare(strict_types=1);

namespace Nomba\Laravel\Tests\Feature;

use Nomba\Laravel\Tests\TestCase;
use Nomba\Sdk\Contracts\NombaClientInterface;
use Nomba\Sdk\NombaClient;
use Nomba\Sdk\Resources\AccountResource;
use Nomba\Sdk\Resources\CheckoutResource;
use Nomba\Sdk\Resources\TransferResource;
use Nomba\Sdk\Resources\VirtualAccountResource;
use Nomba\Sdk\Resources\WebhookManagementResource;

final class ServiceProviderTest extends TestCase
{
    public function test_nomba_client_is_bound_as_singleton(): void
    {
        $app = $this->app;
        $this->assertNotNull($app);
        $a = $app->make(NombaClient::class);
        $b = $app->make(NombaClient::class);

        $this->assertInstanceOf(NombaClient::class, $a);
        $this->assertSame($a, $b);
    }

    public function test_interface_resolves_to_nomba_client(): void
    {
        $app = $this->app;
        $this->assertNotNull($app);
        $this->assertInstanceOf(
            NombaClient::class,
            $app->make(NombaClientInterface::class),
        );
    }

    public function test_config_is_merged_with_defaults(): void
    {
        $app = $this->app;
        $this->assertNotNull($app);
        $this->assertSame('sandbox', $app['config']->get('nomba.environment'));
        $this->assertSame(30.0, $app['config']->get('nomba.timeout'));
        $this->assertSame(3, $app['config']->get('nomba.retry_attempts'));
    }

    public function test_webhook_route_is_registered_by_default(): void
    {
        $this->assertNotNull(
            app('router')->getRoutes()->getByAction(
                'Nomba\Laravel\Http\Controllers\WebhookController@handle'
            )
        );
    }

    public function test_resolved_client_exposes_resources(): void
    {
        $app = $this->app;
        $this->assertNotNull($app);
        $nomba = $app->make(NombaClient::class);

        $this->assertInstanceOf(CheckoutResource::class, $nomba->checkout());
        $this->assertInstanceOf(VirtualAccountResource::class, $nomba->virtualAccounts());
        $this->assertInstanceOf(TransferResource::class, $nomba->transfers());
        $this->assertInstanceOf(AccountResource::class, $nomba->accounts());
        $this->assertInstanceOf(WebhookManagementResource::class, $nomba->webhookManagement());
    }
}
