<?php

declare(strict_types=1);

namespace ProTrafficGroup\OrchidLaraberg;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Orchid\Support\Facades\Dashboard;

class LarabergServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->callAfterResolving('view', static function (ViewFactory  $factory) {
            $factory->composer('platform::app', static function () {
                Dashboard::registerResource('scripts', asset('vendor/protrafficgroup/orchid-laraberg/laraberg.js'));
            });
        });

        $this->offerPublishing();
    }

    public function register()
    {
        $this->loadViewsFrom(__DIR__.'/../views', 'laraberg');

        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laraberg');
    }

    protected function offerPublishing()
    {
        if(! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/protrafficgroup/orchid-laraberg'),
        ], ['laraberg-assets', 'laravel-assets', 'orchid-assets']);

        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('orchid-laraberg.php'),
        ], 'laraberg-config');

        Artisan::call('vendor:publish', [
            '--provider' => 'VanOns\\Laraberg\\LarabergServiceProvider'
        ]);
    }
}
