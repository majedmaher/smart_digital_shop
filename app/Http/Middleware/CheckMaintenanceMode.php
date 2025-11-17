<?php

namespace App\Http\Middleware;

use App\Services\MaintenanceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware for maintenance routes
        if ($this->shouldSkipMiddleware($request)) {
            return $next($request);
        }

        // Check if maintenance mode is active
        if (MaintenanceService::isMaintenanceMode()) {
            return response()->json([
                'success' => false,
                'message' => 'الموقع في وضع الصيانة، يرجى المحاولة لاحقاً',
                'error_code' => 'MAINTENANCE_MODE',
                'data' => []
            ], 503);
        }

        return $next($request);
    }

    /**
     * Determine if middleware should be skipped for this request
     */
    private function shouldSkipMiddleware(Request $request): bool
    {
        // Get route name (e.g., "auth.login", "auth.logout")
        $routeName = $request->route()?->getName();
        
        // List of route names to skip
        $skipRouteNames = [
            'auth.login',
            'auth.logout',
            'auth.loginWithPhone',
            'auth.register',
            'auth.confirmOtp',
        ];
        
        // List of path patterns to skip (using Laravel's is() method)
        $skipPathPatterns = [
            'api/maintenance/*',
            'api/auth/login',
            'api/auth/logout',
            'api/auth/login-phone',
            'api/auth/register',
            'api/auth/register-phone',
            'api/auth/confirm-otp',
        ];

        // Check route name first (most reliable)
        if ($routeName) {
            foreach ($skipRouteNames as $skipRoute) {
                if ($routeName === $skipRoute || str_contains($routeName, $skipRoute)) {
                    return true;
                }
            }
        }
        
        // Check path patterns using Laravel's is() method
        foreach ($skipPathPatterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}

