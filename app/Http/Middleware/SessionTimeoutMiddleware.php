<?php

namespace App\Http\Middleware;

use App\Services\SessionTimeoutService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeoutMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware for certain routes
        if ($this->shouldSkipMiddleware($request)) {
            return $next($request);
        }

        // Check if user is authenticated
        if (!$request->user()) {
            return $next($request);
        }

        $userId = $request->user()->id;

        // Check if session is expired
        if (SessionTimeoutService::isSessionExpired($userId)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.session_expired'),
                'error_code' => 'SESSION_EXPIRED',
                'data' => [
                    'user_id' => $userId,
                    'expired_at' => now()->toDateTimeString(),
                    'timeout_minutes' => SessionTimeoutService::getCurrentTimeout()
                ]
            ], 401);
        }

        // Extend session on each request (optional - you can remove this if you want fixed timeout)
        SessionTimeoutService::setUserSessionTimeout($userId);

        return $next($request);
    }

    /**
     * Determine if middleware should be skipped for this request
     */
    private function shouldSkipMiddleware(Request $request): bool
    {
        $skipRoutes = [
            'api/auth/login',
            'api/auth/register',
            'api/auth/logout',
            'api/session-timeout/set',
            'api/session-timeout/extend',
            'api/session-timeout/clear',
            'api/session-timeout/info',
        ];

        $currentRoute = $request->route()?->uri();

        foreach ($skipRoutes as $skipRoute) {
            if (str_contains($currentRoute, $skipRoute)) {
                return true;
            }
        }

        return false;
    }
}
