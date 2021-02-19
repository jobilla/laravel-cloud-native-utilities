<?php

namespace Jobilla\CloudNative\Laravel;

use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\APC;

class CloudNativeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/metrics.php', 'metrics');

        $this->app->singleton(CollectorRegistry::class, function () {
            return new CollectorRegistry(new APC());
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/logging.php' => config_path('logging.php'),
            __DIR__.'/../config/metrics.php' => config_path('metrics.php'),
        ]);

        if ($this->app['config']->get('metrics.route.enabled')) {

            $router = $this->app['router'];
        }
    }
}