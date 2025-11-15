<?php

namespace App\Providers;

use App\RoleEnum;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // منح دور admin جميع الصلاحيات تلقائياً
        Gate::before(function ($user, $ability) {
            if ($user->hasRole(RoleEnum::ADMIN)) {
                return true;
            }
        });
    }
}
