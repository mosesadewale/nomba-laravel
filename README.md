# nomba-laravel

Laravel integration for the [Nomba PHP SDK](https://github.com/nomba/nomba-php).

## Requirements

- PHP 8.2+
- Laravel 10 or 11
- `nomba/nomba-php` (installed automatically)

## Installation

```bash
composer require nomba/nomba-laravel
```

The service provider is auto-discovered. Publish the config file:

```bash
php artisan vendor:publish --tag=nomba-config
```

## Configuration

Add your credentials to `.env`:

```env
NOMBA_CLIENT_ID=your-client-id
NOMBA_CLIENT_SECRET=your-client-secret
NOMBA_ACCOUNT_ID=your-account-id
NOMBA_ENV=sandbox          # sandbox | production
NOMBA_WEBHOOK_SECRET=your-webhook-secret
```

Full config reference (`config/nomba.php`):

```php
'client_id'              => env('NOMBA_CLIENT_ID', ''),
'client_secret'          => env('NOMBA_CLIENT_SECRET', ''),
'account_id'             => env('NOMBA_ACCOUNT_ID', ''),
'environment'            => env('NOMBA_ENV', 'sandbox'),
'webhook_secret'         => env('NOMBA_WEBHOOK_SECRET', ''),
'webhook_path'           => env('NOMBA_WEBHOOK_PATH', 'nomba/webhook'),
'register_webhook_route' => env('NOMBA_REGISTER_ROUTE', true),
'timeout'                => (int) env('NOMBA_TIMEOUT', 30),
'retry_attempts'         => (int) env('NOMBA_RETRY_ATTEMPTS', 3),
```

## Usage

### Facade

```php
use Nomba\Laravel\Facades\Nomba;

$order = Nomba::checkout()->create([
    'amount'         => 5000,
    'currency'       => 'NGN',
    'customerEmail'  => 'user@example.com',
    'orderReference' => 'order_abc',
]);

echo $order->checkoutLink;
```

### Dependency injection

Inject `NombaClientInterface` (or the concrete `NombaClient`) wherever Laravel resolves dependencies:

```php
use Nomba\Sdk\Contracts\NombaClientInterface;

class PaymentController extends Controller
{
    public function __construct(private readonly NombaClientInterface $nomba) {}

    public function initiate(): JsonResponse
    {
        $order = $this->nomba->checkout()->create([...]);

        return response()->json(['url' => $order->checkoutLink]);
    }
}
```

### Available resources

```php
Nomba::checkout()->create([...]);  // create a payment order
Nomba::checkout()->find($ref);     // fetch by reference

Nomba::virtualAccounts()->create([...]);
Nomba::virtualAccounts()->find($ref);
Nomba::virtualAccounts()->list([...]);

Nomba::transfers()->bankTransfer([...]);
Nomba::transfers()->walletTransfer([...]);
Nomba::transfers()->banks();
```

See the [nomba-php README](https://github.com/nomba/nomba-php) for full parameter shapes and return types.

## Webhooks

### Automatic route (default)

The package registers `POST nomba/webhook` automatically. The route is protected by `VerifyNombaWebhook` middleware, which checks the `nomba-signature` header using HMAC-SHA256/Base64 against `NOMBA_WEBHOOK_SECRET`.

Point your Nomba dashboard callback URL to:

```
https://yourapp.com/nomba/webhook
```

To use a different path:

```env
NOMBA_WEBHOOK_PATH=api/payments/webhook
```

### Listening to events

The controller parses each incoming request and dispatches a Laravel event. Register listeners in `EventServiceProvider`:

```php
use Nomba\Laravel\Events\PaymentSuccess;
use Nomba\Laravel\Events\PaymentFailed;
use Nomba\Laravel\Events\PaymentReversal;
use Nomba\Laravel\Events\PayoutSuccess;
use Nomba\Laravel\Events\PayoutFailed;
use Nomba\Laravel\Events\PayoutRefund;

protected $listen = [
    PaymentSuccess::class  => [HandlePaymentSuccess::class],
    PaymentFailed::class   => [HandlePaymentFailed::class],
    PaymentReversal::class => [HandlePaymentReversal::class],
    PayoutSuccess::class   => [HandlePayoutSuccess::class],
    PayoutFailed::class    => [HandlePayoutFailed::class],
    PayoutRefund::class    => [HandlePayoutRefund::class],
];
```

Each event carries a `WebhookEvent` DTO:

```php
use Nomba\Laravel\Events\PaymentSuccess;
use Nomba\Sdk\DTOs\WebhookEvent;

class HandlePaymentSuccess implements ShouldQueue
{
    public function handle(PaymentSuccess $event): void
    {
        $webhookEvent = $event->event; // WebhookEvent DTO

        $webhookEvent->id;          // requestId from Nomba
        $webhookEvent->type;        // WebhookEventType enum
        $webhookEvent->payload;     // data array from Nomba
        $webhookEvent->occurredAt;  // DateTimeImmutable
    }
}
```

Listeners implementing `ShouldQueue` are automatically processed by your queue worker — no extra job class required.

### Opting out of the automatic route

Set `NOMBA_REGISTER_ROUTE=false` to disable the built-in route and handle the webhook yourself:

```php
// routes/api.php
Route::post('payments/webhook', function (Request $request) {
    $timestamp = $request->header('nomba-timestamp', '');
    $signature = $request->header('nomba-signature', '');

    if (!Nomba::webhooks()->verify($request->getContent(), $timestamp, $signature)) {
        abort(401);
    }

    $event = Nomba::webhooks()->parse($request->getContent(), $timestamp);

    // handle $event yourself
    return response()->json(['received' => true]);
});
```

## Token caching

Tokens are cached in-memory by default, which works for queue workers and Octane. For short-lived PHP-FPM processes, bind a PSR-16 cache in `AppServiceProvider`:

```php
use Nomba\Sdk\Factory;
use Nomba\Sdk\Contracts\NombaClientInterface;
use Symfony\Component\Cache\Psr16Cache;

$this->app->singleton(NombaClientInterface::class, function ($app) {
    return Factory::make(
        clientId:      config('nomba.client_id'),
        clientSecret:  config('nomba.client_secret'),
        accountId:     config('nomba.account_id'),
        webhookSecret: config('nomba.webhook_secret'),
        cache:         new Psr16Cache($app->make(\Psr\Cache\CacheItemPoolInterface::class)),
    );
});
```

## Testing

Bind a `FakeHttpClient` in your test's service container to test without hitting the network:

```php
use Nomba\Sdk\Factory;
use Nomba\Sdk\Support\NombaConfig;
use Nomba\Sdk\Contracts\NombaClientInterface;
use Nomba\Sdk\Tests\Fakes\FakeHttpClient;
use Nomba\Sdk\Enums\Environment;

protected function setUp(): void
{
    parent::setUp();

    $this->app->bind(NombaClientInterface::class, function () {
        return Factory::withClient(
            new FakeHttpClient(['data' => ['orderReference' => 'ref_001', 'checkoutLink' => 'https://...']]),
            new NombaConfig(clientId: 'test', clientSecret: 'test', accountId: 'test'),
        );
    });
}
```

## License

MIT
