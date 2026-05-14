<?php

declare(strict_types=1);

namespace Nomba\Laravel\Events;

use Nomba\Sdk\DTOs\WebhookEvent;

final class PaymentFailed
{
    public function __construct(public readonly WebhookEvent $event) {}
}
