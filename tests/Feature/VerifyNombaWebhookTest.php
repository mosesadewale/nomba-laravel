<?php

declare(strict_types=1);

namespace Nomba\Laravel\Tests\Feature;

use Illuminate\Http\Request;
use Nomba\Laravel\Http\Middleware\VerifyNombaWebhook;
use Nomba\Laravel\Tests\TestCase;
use Nomba\Sdk\NombaClient;
use Symfony\Component\HttpFoundation\Response;

final class VerifyNombaWebhookTest extends TestCase
{
    private const SECRET = 'test-secret';

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('nomba.webhook_secret', self::SECRET);
    }

    /** @return array<string, mixed> */
    private function nombaPayload(): array
    {
        return [
            'requestId'  => 'req_001',
            'event_type' => 'payment_success',
            'data'       => [
                'merchant'    => ['userId' => 'user_001', 'walletId' => 'wallet_001'],
                'transaction' => ['transactionId' => 'txn_001', 'type' => 'vact_transfer', 'time' => '2026-01-01T00:00:00Z', 'responseCode' => ''],
                'customer'    => [],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildSignature(array $payload, string $timestamp): string
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

        return base64_encode(hash_hmac('sha256', $signString, self::SECRET, true));
    }

    private function request(string $body, string $signature, string $timestamp): Request
    {
        $request = Request::create('/nomba/webhook', 'POST', content: $body);
        $request->headers->set('nomba-signature', $signature);
        $request->headers->set('nomba-timestamp', $timestamp);

        return $request;
    }

    private function middleware(): VerifyNombaWebhook
    {
        $app = $this->app;
        $this->assertNotNull($app);

        return new VerifyNombaWebhook($app->make(NombaClient::class));
    }

    public function test_valid_signature_passes_through(): void
    {
        $payload   = $this->nombaPayload();
        $timestamp = '2026-01-01T00:00:00Z';
        $body      = (string) json_encode($payload);
        $signature = $this->buildSignature($payload, $timestamp);

        $response = $this->middleware()->handle(
            $this->request($body, $signature, $timestamp),
            fn () => new Response('ok'),
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_invalid_signature_returns_401(): void
    {
        $payload   = $this->nombaPayload();
        $timestamp = '2026-01-01T00:00:00Z';
        $body      = (string) json_encode($payload);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->middleware()->handle(
            $this->request($body, 'bad-signature', $timestamp),
            fn () => new Response('ok'),
        );
    }

    public function test_missing_signature_returns_401(): void
    {
        $payload = $this->nombaPayload();
        $body    = (string) json_encode($payload);
        $request = Request::create('/nomba/webhook', 'POST', content: $body);
        $request->headers->set('nomba-timestamp', '2026-01-01T00:00:00Z');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->middleware()->handle(
            $request,
            fn () => new Response('ok'),
        );
    }
}
