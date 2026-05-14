<?php

declare(strict_types=1);

namespace Nomba\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Nomba\Sdk\Contracts\NombaClientInterface;
use Nomba\Sdk\Resources\AccountResource;
use Nomba\Sdk\Resources\CheckoutResource;
use Nomba\Sdk\Resources\TransferResource;
use Nomba\Sdk\Resources\VirtualAccountResource;
use Nomba\Sdk\Resources\WebhookManagementResource;
use Nomba\Sdk\Resources\WebhookResource;

/**
 * @method static CheckoutResource          checkout()
 * @method static VirtualAccountResource    virtualAccounts()
 * @method static TransferResource          transfers()
 * @method static AccountResource           accounts()
 * @method static WebhookManagementResource webhookManagement()
 * @method static WebhookResource           webhooks()
 *
 * @see \Nomba\Sdk\NombaClient
 */
class Nomba extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NombaClientInterface::class;
    }
}
