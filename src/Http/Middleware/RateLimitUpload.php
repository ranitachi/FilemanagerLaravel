<?php

namespace Fachran\FileManager\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;

class RateLimitUpload
{
    public function __construct(protected RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $key     = 'fm_upload:'.($request->user()?->getKey() ?? $request->ip());
        $maxPerMinute = config('filemanager.rate_limit.uploads_per_minute', 20);

        if ($this->limiter->tooManyAttempts($key, $maxPerMinute)) {
            $seconds = $this->limiter->availableIn($key);
            return response()->json([
                'message' => "Too many uploads. Retry in {$seconds} seconds.",
            ], 429);
        }

        $this->limiter->hit($key, 60);

        return $next($request);
    }
}
