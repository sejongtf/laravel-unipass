<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass;

use Illuminate\Support\ServiceProvider;
use Sejongtf\LaravelUnipass\Console\TrackCommand;
use Sejongtf\LaravelUnipass\Contracts\Client as ClientContract;
use Sejongtf\LaravelUnipass\Http\Client as HttpClient;

class UnipassServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/unipass.php', 'unipass');

        $this->app->singleton(ClientContract::class, fn ($app) => new HttpClient($app['config']['unipass'] ?? []));
        $this->app->alias(ClientContract::class, 'unipass.client');

        $this->app->singleton(Unipass::class, fn ($app) => new Unipass(
            $app->make(ClientContract::class),
            $app['config']['unipass'] ?? [],
        ));
        $this->app->alias(Unipass::class, 'unipass');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/unipass.php' => config_path('unipass.php'),
            ], 'unipass-config');

            $this->commands([
                TrackCommand::class,
            ]);
        }
    }
}
