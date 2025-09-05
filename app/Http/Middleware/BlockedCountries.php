<?php

namespace App\Http\Middleware;

use App\Http\Controllers\API\BaseController;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Stevebauman\Location\Facades\Location;
use Symfony\Component\HttpFoundation\Response;

class BlockedCountries
{
    public function handle(Request $request, Closure $next): Response
    {
        $blockedCountries = explode(',', env('FRAUD_COUNTRIES', ''));

        if (empty($blockedCountries)) {
            return $next($request);
        }

        $ip = $request->ip(); // أو استخدم $ip = '213.6.144.81'; للتجربة

        // إضافة الكاش: احتفظ بنتيجة الموقع لمدة 24 ساعة
        // $position = Cache::remember("location.{$ip}", 3600, function () use ($ip) {
        //     return Location::get($ip);
        // });

        // if ($position && in_array($position->countryCode, $blockedCountries)) {
        //     return BaseController::sendError(__('messages.access_blocked'), [$ip, $position->countryCode], 403);
        // }

        return $next($request);
    }
}
