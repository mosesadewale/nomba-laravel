<?php

declare(strict_types=1);

namespace Nomba\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nomba\Laravel\Events\PaymentFailed;
use Nomba\Laravel\Events\PaymentReversal;
use Nomba\Laravel\Events\PaymentSuccess;
use Nomba\Laravel\Events\PayoutFailed;
use Nomba\Laravel\Events\PayoutRefund;
use Nomba\Laravel\Events\PayoutSuccess;
use Nomba\Sdk\Enums\WebhookEventType;
use Nomba\Sdk\Exceptions\WebhookException;
use Nomba\Sdk\NombaClient;

class WebhookController extends Controller
{
    public function __construct(private readonly NombaClient $nomba) {}

    public function handle(Request $request): JsonResponse
    {
        $timestamp = (string) $request->header('nomba-timestamp', '');

        try {
            $event = $this->nomba->webhooks()->parse($request->getContent(), $timestamp);
        } catch (WebhookException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        match ($event->type) {
            WebhookEventType::PaymentSuccess  => event(new PaymentSuccess($event)),
            WebhookEventType::PaymentFailed   => event(new PaymentFailed($event)),
            WebhookEventType::PaymentReversal => event(new PaymentReversal($event)),
            WebhookEventType::PayoutSuccess   => event(new PayoutSuccess($event)),
            WebhookEventType::PayoutFailed    => event(new PayoutFailed($event)),
            WebhookEventType::PayoutRefund    => event(new PayoutRefund($event)),
            default                           => null,
        };

        return response()->json(['received' => true]);
    }
}
