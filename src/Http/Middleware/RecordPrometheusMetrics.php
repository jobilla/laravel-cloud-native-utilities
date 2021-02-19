<?php

namespace Jobilla\CloudNative\Laravel\Http\Middleware;

use Illuminate\Http\Request;
use Prometheus\CollectorRegistry;
use Symfony\Component\HttpFoundation\Response;

class RecordPrometheusMetrics
{
    /**
     * @var CollectorRegistry
     */
    protected CollectorRegistry $registry;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
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
        if ($route === 'metrics') {
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
            [0.1, 0.2, 0.3, 0.4, 0.5, 0.75, 1.0, 2.5, 5.0]
        );

        $labels = [$route, $request->method(), $response->getStatusCode()];

        $requestCount->inc($labels);
        $timeHistogram->observe($completedAt - LARAVEL_START, $labels);
    }
}