<?php

declare(strict_types=1);

namespace Nomba\Laravel\Tests\Unit;

use Nomba\Laravel\Facades\Nomba;
use Nomba\Laravel\Tests\TestCase;
use Nomba\Sdk\Resources\AccountResource;
use Nomba\Sdk\Resources\CheckoutResource;
use Nomba\Sdk\Resources\TransferResource;
use Nomba\Sdk\Resources\VirtualAccountResource;
use Nomba\Sdk\Resources\WebhookManagementResource;
use Nomba\Sdk\Resources\WebhookResource;

final class NombaFacadeTest extends TestCase
{
    public function test_facade_resolves_checkout(): void
    {
        $this->assertInstanceOf(CheckoutResource::class, Nomba::checkout());
    }

    public function test_facade_resolves_virtual_accounts(): void
    {
        $this->assertInstanceOf(VirtualAccountResource::class, Nomba::virtualAccounts());
    }

    public function test_facade_resolves_transfers(): void
    {
        $this->assertInstanceOf(TransferResource::class, Nomba::transfers());
    }

    public function test_facade_resolves_accounts(): void
    {
        $this->assertInstanceOf(AccountResource::class, Nomba::accounts());
    }

    public function test_facade_resolves_webhook_management(): void
    {
        $this->assertInstanceOf(WebhookManagementResource::class, Nomba::webhookManagement());
    }

    public function test_facade_resolves_webhooks(): void
    {
        $this->assertInstanceOf(WebhookResource::class, Nomba::webhooks());
    }
}
