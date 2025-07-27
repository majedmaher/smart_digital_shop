<?php

namespace App\Http\Controllers;

use App\Services\SocialAuthService;
use Illuminate\Http\Request;

class SocialAuthController extends Controller
{
    public function redirect($provider)
    {
        return SocialAuthService::redirect($provider);
    }
    public function callback($provider)
    {
        return SocialAuthService::callback($provider);
    }
    public function socialLogin(Request $request)
    {
        $request->validate([
            'provider' => 'required|in:google,facebook',
            'access_token' => 'required|string',
        ]);
        return SocialAuthService::socialLogin($request->validated());
    }
}
