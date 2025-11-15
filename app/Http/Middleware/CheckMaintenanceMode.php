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
        $skipRoutes = [
            'api/maintenance/status',
            'api/maintenance/toggle',
            'api/auth/login',
            'api/auth/logout',
            'api/auth/login-phone',
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

