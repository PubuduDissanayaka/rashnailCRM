<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitReportExports
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'report_exports:' . $request->user()?->id ?: $request->ip();

        // Configure rate limiting: max 5 exports per minute
        $maxAttempts = 5;
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);
            
            return response()->json([
                'success' => false,
                'message' => 'Too many export requests. Please try again in ' . $retryAfter . ' seconds.',
                'retry_after' => $retryAfter,
            ], 429);
        }

        RateLimiter::hit($key, $decaySeconds);

        $response = $next($request);

        // Add rate limit headers to response
        return $this->addRateLimitHeaders(
            $response,
            $maxAttempts,
            RateLimiter::remaining($key, $maxAttempts),
            RateLimiter::availableIn($key)
        );
    }

    /**
     * Add rate limit headers to response
     *
     * @param Response $response
     * @param int $maxAttempts
     * @param int $remaining
     * @param int $retryAfter
     * @return Response
     */
    private function addRateLimitHeaders(Response $response, int $maxAttempts, int $remaining, int $retryAfter): Response
    {
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $remaining);
        $response->headers->set('X-RateLimit-Reset', time() + $retryAfter);

        return $response;
    }
}