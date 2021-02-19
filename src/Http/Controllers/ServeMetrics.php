<?php

namespace Jobilla\CloudNative\Laravel\Http\Controllers;

use Illuminate\Http\Response;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

class ServeMetrics
{
    public function __invoke(CollectorRegistry $registry)
    {
        $metrics = $registry->getMetricFamilySamples();
        $renderer = new RenderTextFormat();

        return new Response(
            $renderer->render($metrics),
            200,
            ['Content-Type' => RenderTextFormat::MIME_TYPE]
        );
    }
}
