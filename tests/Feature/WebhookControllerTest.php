<?php

declare(strict_types=1);

namespace Nomba\Laravel\Tests\Feature;

use Nomba\Laravel\Events\PaymentFailed;
use Nomba\Laravel\Events\PaymentSuccess;
use Nomba\Laravel\Events\PayoutSuccess;
use Nomba\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;

final class WebhookControllerTest extends TestCase
{
    private const SECRET = 'test-secret';

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('nomba.webhook_secret', self::SECRET);
    }

    /** @return array<string, mixed> */
    private function payload(string $eventType = 'payment_success', string $requestId = 'req_001'): array
    {
        return [
            'requestId'  => $requestId,
            'event_type' => $eventType,
            'data'       => [
                'merchant'    => ['userId' => 'user_001', 'walletId' => 'wallet_001'],
                'transaction' => ['transactionId' => 'txn_001', 'type' => 'vact_transfer', 'time' => '2026-01-01T00:00:00Z', 'responseCode' => ''],
                'customer'    => [],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return TestResponse<\Illuminate\Http\Response>
     */
    private function postWebhook(array $payload, string $timestamp = '2026-01-01T00:00:00Z'): TestResponse
    {
        $signString = implode(':', [
            $payload['event_type']                           ?? '',
            $payload['requestId']                            ?? '',
            $payload['data']['merchant']['userId']           ?? '',
            $payload['data']['merchant']['walletId']         ?? '',
            $payload['data']['transaction']['transactionId'] ?? '',
            $payload['data']['transaction']['type']          ?? '',
            $payload['data']['transaction']['time']          ?? '',
            $payload['data']['transaction']['responseCode']  ?? '',
            $timestamp,
        ]);
        $signature = base64_encode(hash_hmac('sha256', $signString, self::SECRET, true));

        return $this->call(
            'POST',
            config('nomba.webhook_path'),
            [],
            [],
            [],
            [
                'HTTP_NOMBA_SIGNATURE' => $signature,
                'HTTP_NOMBA_TIMESTAMP' => $timestamp,
                'CONTENT_TYPE'         => 'application/json',
            ],
            (string) json_encode($payload),
        );
    }

    public function test_payment_success_event_is_dispatched(): void
    {
        Event::fake();

        $this->postWebhook($this->payload('payment_success', 'req_001'))
            ->assertOk()
            ->assertJson(['received' => true]);

        Event::assertDispatched(PaymentSuccess::class, function ($e) {
            return $e->event->id === 'req_001';
        });
    }

    public function test_payment_failed_event_is_dispatched(): void
    {
        Event::fake();

        $this->postWebhook($this->payload('payment_failed', 'req_002'))->assertOk();

        Event::assertDispatched(PaymentFailed::class);
    }

    public function test_payout_success_event_is_dispatched(): void
    {
        Event::fake();

        $this->postWebhook($this->payload('payout_success', 'req_003'))->assertOk();

        Event::assertDispatched(PayoutSuccess::class);
    }

    public function test_invalid_signature_returns_401(): void
    {
        $payload   = $this->payload();
        $timestamp = '2026-01-01T00:00:00Z';

        $this->call(
            'POST',
            config('nomba.webhook_path'),
            [],
            [],
            [],
            [
                'HTTP_NOMBA_SIGNATURE' => 'bad-sig',
                'HTTP_NOMBA_TIMESTAMP' => $timestamp,
                'CONTENT_TYPE'         => 'application/json',
            ],
            (string) json_encode($payload),
        )->assertStatus(401);
    }

    public function test_unknown_event_type_returns_ok_without_dispatching(): void
    {
        Event::fake();

        $this->postWebhook($this->payload('some_unknown_event', 'req_004'))->assertOk();

        Event::assertNotDispatched(PaymentSuccess::class);
        Event::assertNotDispatched(PaymentFailed::class);
        Event::assertNotDispatched(PayoutSuccess::class);
    }

    public function test_malformed_payload_returns_400_after_valid_signature(): void
    {
        // Payload passes signature check (empty string produces a valid HMAC),
        // but lacks requestId so EventParser throws WebhookException.
        $body      = '{"event_type":"payment_success","data":{"merchant":{"userId":"u","walletId":"w"},"transaction":{"transactionId":"t","type":"x","time":"2026-01-01T00:00:00Z","responseCode":""}}}';
        $timestamp = '2026-01-01T00:00:00Z';

        // Build a valid signature matching the known literal body values.
        // requestId is intentionally absent — sign string uses '' in that slot.
        $signString = implode(':', [
            'payment_success', '', 'u', 'w', 't', 'x', '2026-01-01T00:00:00Z', '', $timestamp,
        ]);
        $signature = base64_encode(hash_hmac('sha256', $signString, self::SECRET, true));

        $this->call(
            'POST',
            config('nomba.webhook_path'),
            [],
            [],
            [],
            [
                'HTTP_NOMBA_SIGNATURE' => $signature,
                'HTTP_NOMBA_TIMESTAMP' => $timestamp,
                'CONTENT_TYPE'         => 'application/json',
            ],
            $body,
        )->assertStatus(400);
    }
}
