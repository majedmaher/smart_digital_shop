<?php

use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\BlockedCountries;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\CustomDynamicPermissionMiddleware;
use App\Http\Middleware\SessionTimeoutMiddleware;
use App\Http\Middleware\SuspiciousTransactionMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->use([
            \App\Http\Middleware\SetLocale::class,
        ]);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'custom_permission' => CustomDynamicPermissionMiddleware::class,
            'should_auth' => AuthMiddleware::class,
            'not_blocked_country' => BlockedCountries::class,
            'throttle.by-route' => \App\Http\Middleware\RateLimitByRoute::class,
            'session_timeout' => SessionTimeoutMiddleware::class,
            'suspicious_transaction' => SuspiciousTransactionMiddleware::class,
            'check_maintenance' => CheckMaintenanceMode::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
