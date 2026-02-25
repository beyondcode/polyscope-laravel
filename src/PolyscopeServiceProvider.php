<?php

declare(strict_types=1);

namespace Polyscope\Laravel;

use Illuminate\Support\ServiceProvider;

class PolyscopeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PolyscopeManager::class, function ($app): PolyscopeManager {
            $token = $app['config']->get('services.polyscope.token');
            $url = $app['config']->get('services.polyscope.url');

            return new PolyscopeManager(
                is_string($token) ? $token : null,
                is_string($url) ? $url : null,
            );
        });
    }
}
