<?php

declare(strict_types=1);

namespace Nomba\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Nomba\Sdk\NombaClient;
use Symfony\Component\HttpFoundation\Response;

class VerifyNombaWebhook
{
    public function __construct(private readonly NombaClient $nomba) {}

    public function handle(Request $request, Closure $next): Response
    {
        $signature = (string) $request->header('nomba-signature', '');
        $timestamp = (string) $request->header('nomba-timestamp', '');
        $raw       = $request->getContent();

        if (!$this->nomba->webhooks()->verify($raw, $timestamp, $signature)) {
            abort(401, 'Invalid webhook signature.');
        }

        return $next($request);
    }
}
