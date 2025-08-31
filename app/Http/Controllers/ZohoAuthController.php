<?php

namespace App\Http\Controllers;

use App\Models\ZohoToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ZohoAuthController extends Controller
{
    public function connect()
    {
        $params = [
            'response_type' => 'code',
            'client_id'     => config('services.zoho.client_id'),
            'scope'         => 'ZohoBooks.fullaccess.all', // غطّ الكل أو استخدم سكووبات أدق
            'redirect_uri'  => config('services.zoho.redirect_uri'),
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ];
        $authUrl = rtrim(config('services.zoho.accounts_url'), '/') . '/oauth/v2/auth?' . http_build_query($params);
        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');
        abort_if(!$code, 400, 'Missing code');

        $resp = Http::asForm()->post(
            rtrim(config('services.zoho.accounts_url'), '/') . '/oauth/v2/token',
            [
                'grant_type'    => 'authorization_code',
                'client_id'     => config('services.zoho.client_id'),
                'client_secret' => config('services.zoho.client_secret'),
                'redirect_uri'  => config('services.zoho.redirect_uri'),
                'code'          => $code,
            ]
        )->json();

        if (!isset($resp['access_token'])) {
            return response()->json(['error' => 'OAuth failed', 'details' => $resp], 500);
        }

        ZohoToken::query()->delete(); // نخزّن واحد فقط
        ZohoToken::create([
            'access_token'  => $resp['access_token'],
            'refresh_token' => $resp['refresh_token'] ?? null,
            'expires_at'    => now()->addSeconds($resp['expires_in'] ?? 3300),
            'scope'         => $resp['scope'] ?? null,
        ]);

        return redirect('/')->with('status', 'Zoho connected');
    }
}
