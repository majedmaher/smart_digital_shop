<?php

namespace App\Http\Middleware;

use App\Http\Controllers\API\BaseController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // return BaseController::sendError(__('messages.require_login'), [auth('sanctum')->user()], 401);
        if (auth('sanctum')->user() == null) {
            return BaseController::sendError(__('messages.require_login'), [], 401);
        }
        return $next($request);
    }
}
