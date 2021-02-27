<?php

namespace Jobilla\CloudNative\Laravel\Http\Middleware;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Prometheus\CollectorRegistry;
use Symfony\Component\HttpFoundation\Response;

class RecordPrometheusMetrics
{
    /**
     * @var CollectorRegistry
     */
    protected CollectorRegistry $registry;
    /**
     * @var Repository
     */
    private Repository $config;

    public function __construct(CollectorRegistry $registry, Repository $config)
    {
        $this->registry = $registry;
        $this->config = $config;
    }

    public function handle(Request $request, $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response)
    {
        if (! defined('LARAVEL_START')) {
            return;
        }
        $route = $request->route()->uri();
        if ($route === trim($this->config->get('metrics.route.path'), '/')) {
            return;
        }

        $completedAt = microtime(true);


        $labelNames = ['route', 'method', 'status'];
        $requestCount = $this->registry->getOrRegisterCounter(
            'http',
            'requests_total',
            'Counter for total requests received',
            $labelNames,
        );

        $timeHistogram = $this->registry->getOrRegisterHistogram(
            'http',
            'request_duration_seconds',
            'Duration of HTTP requests in seconds',
            $labelNames,
            $this->config->get('metrics.buckets'),
        );

        $labels = [$route, $request->method(), $response->getStatusCode()];

        $requestCount->inc($labels);
        $timeHistogram->observe($completedAt - LARAVEL_START, $labels);
    }
}