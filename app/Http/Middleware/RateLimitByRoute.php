<?php

namespace App\Http\Middleware;

use App\Http\Controllers\API\BaseController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitByRoute
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $maxAttempts = 5, $decayMinutes = 1): Response
    {
        $key = $request->ip() . '|' . $request->route()->uri(); // IP + المسار

        $results = RateLimiter::attempt(
            $key,
            $maxAttempts,
            function () use ($request) {
                return true;
            },
            $decayMinutes * 60
        );

        if (! $results) {
            return BaseController::sendError(__('messages.too_many_attempts'), [], 429);
        }

        return $next($request);
    }
}
