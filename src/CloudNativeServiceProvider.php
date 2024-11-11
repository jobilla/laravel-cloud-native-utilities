<?php

namespace Jobilla\CloudNative\Laravel;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Jobilla\CloudNative\Laravel\Http\Controllers\ServeMetrics;
use Jobilla\CloudNative\Laravel\Http\Middleware\RecordPrometheusMetrics;
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

    public function boot(Repository $config, Kernel $kernel)
    {
        $this->publishes([
            __DIR__.'/../config/metrics.php' => config_path('metrics.php'),
        ], 'cloud-native-metrics');

        $this->publishes([
            __DIR__.'/../assets/Dockerfile' => base_path('Dockerfile'),
        ], 'dockerfile');

        if ($config->get('metrics.route.enabled')) {
            /** @var Router $router */
            $router = $this->app['router'];
            $router->get($config->get('metrics.route.path'), ServeMetrics::class);
            $kernel->prependMiddleware(RecordPrometheusMetrics::class);
            $kernel->prependMiddleware(LogRequest::class);
        }
    }
}