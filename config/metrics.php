<?php

return [
    // Allowed values: 'redis', 'apc', 'apcng', 'memory' (default)
    'adapter' => env('METRICS_ADAPTER', 'memory'),

    // What metrics do we want to report on?
    'handle' => [
        'database' => env('METRICS_HANDLE_DATABASE', true),
        'http' => env('METRICS_HANDLE_HTTP', true),
    ],

    // The route configuration controls which path the metrics data is served on
    'route' => [
        'enabled' => env('ENABLE_METRICS', true),
        'path' => '/metrics',
    ],

    // Here you may specify which buckets to use for prometheus
    'buckets' => [0.1, 0.2, 0.3, 0.4, 0.5, 0.75, 1.0, 2.5, 5.0],
];
