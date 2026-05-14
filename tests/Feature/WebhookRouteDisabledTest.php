<?php

declare(strict_types=1);

namespace Nomba\Laravel\Tests\Feature;

use Nomba\Laravel\Tests\TestCase;

final class WebhookRouteDisabledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('nomba.register_webhook_route', false);
    }

    public function test_webhook_route_is_not_registered_when_disabled(): void
    {
        $this->assertNull(
            app('router')->getRoutes()->getByAction(
                'Nomba\Laravel\Http\Controllers\WebhookController@handle'
            )
        );
    }
}
