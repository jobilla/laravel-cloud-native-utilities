<?php

namespace Jobilla\CloudNative\Laravel;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Jobilla\CloudNative\Laravel\Exceptions\UnsupportedAdapterException;
use Jobilla\CloudNative\Laravel\Http\Controllers\ServeMetrics;
use Jobilla\CloudNative\Laravel\Http\Middleware\RecordPrometheusMetrics;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\APC;
use Prometheus\Storage\APCng;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;

class CloudNativeServiceProvider extends ServiceProvider
{
    protected CollectorRegistry $registry;

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/metrics.php', 'metrics');
    }

    public function boot(Repository $config, Kernel $kernel)
    {
        $this->publishes([
            __DIR__.'/../config/metrics.php' => config_path('metrics.php'),
        ], 'cloud-native-metrics');

        $this->publishes([
            __DIR__.'/../assets/Dockerfile' => base_path('Dockerfile'),
        ], 'dockerfile');

        $this->app->singleton(CollectorRegistry::class, function () use ($config) {
            $adapter = $config->get('metrics.adapter', 'memory');

            switch ($adapter) {
                case 'redis':
                    return new CollectorRegistry(new Redis);
                break;
                case 'apc':
                    if (filter_var(ini_get('apcu.enable_cli'), FILTER_VALIDATE_BOOLEAN) === false && App::runningInConsole()) {
                        Log::warning('Metrics adapter temporarily turned to "memory" because apc is disabled in CLI. See: https://www.php.net/manual/en/apcu.configuration.php');
                        return new CollectorRegistry(new InMemory);
                    }

                    return new CollectorRegistry(new APC);
                break;
                case 'apcng':
                    return new CollectorRegistry(new APCng);
                break;
                case 'memory':
                    return new CollectorRegistry(new InMemory);
                break;
                default:
                    throw new UnsupportedAdapterException("Adapter `{$adapter}` is not supported.");
                break;
            }
        });

        $this->registry = $this->app->make(CollectorRegistry::class);

        if ($this->registry && $config->get('metrics.handle.database')) {
            DB::listen(function(QueryExecuted $query) use ($config) {
                $query_labels = ['query' => $query->sql, 'mode' => strtok($query->sql, ' ')];

                $query_counter = $this->registry->getOrRegisterCounter('db', 'query_total', 'Counter of total queries', array_keys($query_labels));
                $query_counter->inc(array_values($query_labels));

                $query_duration = $this->registry->getOrRegisterHistogram('db', 'query_duration_ms', 'Duration of query', array_keys($query_labels), $config->get('metrics.buckets'));
                $query_duration->observe($query->time, array_values($query_labels));
            });
        }

        if ($this->registry && $config->get('metrics.handle.http')) {
            $kernel->prependMiddleware(RecordPrometheusMetrics::class);
        }

        if ($this->registry && $config->get('metrics.route.enabled')) {
            /** @var Router $router */
            $router = $this->app['router'];
            $router->get($config->get('metrics.route.path'), ServeMetrics::class);
        }
    }
}