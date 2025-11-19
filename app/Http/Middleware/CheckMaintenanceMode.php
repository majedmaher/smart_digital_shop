<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\RoleEnum;
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
        // Skip middleware for maintenance management routes
        if ($this->isMaintenanceManagementRoute($request)) {
            return $next($request);
        }

        // Check if maintenance mode is active
        if (!MaintenanceService::isMaintenanceMode()) {
            return $next($request);
        }

        // في وضع الصيانة، التحقق من صلاحيات المستخدم للـ auth routes
        if ($this->isAuthRoute($request)) {
            if ($this->userHasPermissions($request)) {
                // المستخدم لديه صلاحيات - السماح له بالدخول/الخروج
                return $next($request);
            } else {
                // المستخدم ليس لديه صلاحيات - رفض الطلب
                return response()->json([
                    'success' => false,
                    'message' => 'الموقع في وضع الصيانة، يرجى المحاولة لاحقاً',
                    'error_code' => 'MAINTENANCE_MODE',
                    'data' => []
                ], 503);
            }
        }

        // لجميع المسارات الأخرى في وضع الصيانة
        return response()->json([
            'success' => false,
            'message' => 'الموقع في وضع الصيانة، يرجى المحاولة لاحقاً',
            'error_code' => 'MAINTENANCE_MODE',
            'data' => []
        ], 503);
    }

    /**
     * Check if the route is a maintenance management route
     */
    private function isMaintenanceManagementRoute(Request $request): bool
    {
        $routeName = $request->route()?->getName();
        $pathPatterns = [
            'api/maintenance/*',
        ];

        if ($routeName && str_contains($routeName, 'maintenance')) {
            return true;
        }

        foreach ($pathPatterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the route is an authentication route
     */
    private function isAuthRoute(Request $request): bool
    {
        $routeName = $request->route()?->getName();
        $authRouteNames = [
            'auth.login',
            'auth.logout',
            'auth.loginWithPhone',
            'auth.confirmOtp',
        ];

        $authPathPatterns = [
            'api/auth/login',
            'api/auth/logout',
            'api/auth/login-phone',
            'api/auth/confirm-otp',
        ];

        // Check route name
        if ($routeName) {
            foreach ($authRouteNames as $authRoute) {
                if ($routeName === $authRoute || str_contains($routeName, $authRoute)) {
                    return true;
                }
            }
        }

        // Check path patterns
        foreach ($authPathPatterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has permissions (for login/logout in maintenance mode)
     */
    private function userHasPermissions(Request $request): bool
    {
        // للـ logout: التحقق من المستخدم المصادق عليه
        if ($request->route()?->getName() === 'auth.logout' || $request->is('api/auth/logout')) {
            $user = $request->user();
            if ($user) {
                // التحقق من أن المستخدم لديه دور admin أو لديه أي صلاحيات
                return $user->hasRole(RoleEnum::ADMIN) || $user->getAllPermissions()->count() > 0;
            }
            return false;
        }

        // للـ login: التحقق من email/phone في قاعدة البيانات
        if ($request->route()?->getName() === 'auth.login' || $request->is('api/auth/login')) {
            $email = $request->input('email');
            if ($email) {
                $user = User::where('email', $email)->first();
                if ($user) {
                    // التحقق من أن المستخدم لديه دور admin أو لديه أي صلاحيات
                    return $user->hasRole(RoleEnum::ADMIN) || $user->getAllPermissions()->count() > 0;
                }
            }
            return false;
        }

        // للـ login-phone: التحقق من phone في قاعدة البيانات
        if ($request->route()?->getName() === 'auth.loginWithPhone' || $request->is('api/auth/login-phone')) {
            $phone = $request->input('phone');
            if ($phone) {
                $user = User::where('phone', $phone)->first();
                if ($user) {
                    // التحقق من أن المستخدم لديه دور admin أو لديه أي صلاحيات
                    return $user->hasRole(RoleEnum::ADMIN) || $user->getAllPermissions()->count() > 0;
                }
            }
            return false;
        }

        // للـ confirm-otp: التحقق من email/phone في قاعدة البيانات
        if ($request->route()?->getName() === 'auth.confirmOtp' || $request->is('api/auth/confirm-otp')) {
            $email = $request->input('email');
            $phone = $request->input('phone');
            
            $user = null;
            if ($email) {
                $user = User::where('email', $email)->first();
            } elseif ($phone) {
                $user = User::where('phone', $phone)->first();
            }

            if ($user) {
                // التحقق من أن المستخدم لديه دور admin أو لديه أي صلاحيات
                return $user->hasRole(RoleEnum::ADMIN) || $user->getAllPermissions()->count() > 0;
            }
            return false;
        }

        return false;
    }
}

