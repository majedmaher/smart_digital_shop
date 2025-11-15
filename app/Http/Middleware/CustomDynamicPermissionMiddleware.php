<?php

namespace App\Http\Middleware;

use App\Http\Controllers\API\BaseController;
use App\RoleEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomDynamicPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission): Response
    {
        $user = auth()->user();

        // إذا كان المستخدم لديه دور admin، يمنحه جميع الصلاحيات تلقائياً
        if ($user->hasRole(RoleEnum::ADMIN)) {
            return $next($request);
        }

        // إذا كانت الكلمة تحتوي على "role:" أو "permission:"
        if (strpos($permission, 'role:') === 0) {
            $roleName = substr($permission, 5); // نزيل "role:" للحصول على اسم الدور
            if (! $user->hasRole($roleName)) {
                // إذا لم يكن لدى المستخدم هذا الدور
                return BaseController::sendError(__('messages.do_not_have_permission'), [], 403);
            }
        } elseif (strpos($permission, 'permission:') === 0) {
            $permissionName = substr($permission, 11); // نزيل "permission:" للحصول على اسم الصلاحية
            if (! $user->hasPermissionTo($permissionName)) {
                // إذا لم يكن لدى المستخدم هذه الصلاحية
                return BaseController::sendError(__('messages.do_not_have_permission'), [], 403);
            }
        } else {
            // إذا كانت الكلمة غير معروفة (لا "role:" ولا "permission:")
            return BaseController::sendError(__('messages.invalid_permission_type'), [], 400);
        }

        return $next($request);
    }
}
