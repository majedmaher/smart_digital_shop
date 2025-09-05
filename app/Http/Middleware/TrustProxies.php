<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * قائمة الـ Proxies الموثوقة
     * نثق بجميع الـ Proxies (آمن لأن السيرفر داخلي)
     */
    protected $proxies = '*';

    /**
     * الصلاحيات المستخدمة من قبل الـ Proxy
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
