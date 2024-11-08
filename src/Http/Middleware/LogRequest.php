<?php

namespace LaravelCloudNative\Utilities\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class LogRequest
{
    public function handle(Request $request, Closure $next)
    {
        // Generate unique request ID
        $requestId = Str::uuid()->toString();
        Context::setHidden('requestStart', microtime(true));
        Context::set('request_id', $requestId);

        // Log request start
        Log::info("Request started", [
            'event' => 'http_request.started',
            'request_id' => $requestId,
            'path' => $request->path(),
            'method' => $request->method(),
        ]);

        return $next($request);
    }

    public function terminate(Request $request, $response)
    {
        // Calculate duration in milliseconds
        $duration = round((microtime(true) - Context::getHidden('requestStart')) * 1000, 2);

        // Log request completion
        Log::info("Request completed", [
            'event' => 'http_request.completed',
            'path' => $request->path(),
            'status' => $response->status(),
            'duration_ms' => $duration,
        ]);
    }
}
