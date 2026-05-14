<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Nomba\Laravel\Http\Controllers\WebhookController;
use Nomba\Laravel\Http\Middleware\VerifyNombaWebhook;

Route::post(
    config('nomba.webhook_path', 'nomba/webhook'),
    [WebhookController::class, 'handle'],
)->middleware(VerifyNombaWebhook::class);
