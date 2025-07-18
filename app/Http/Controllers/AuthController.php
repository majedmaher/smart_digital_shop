<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    // Register
    public function register(RegisterRequest $request)
    {
        return AuthService::register($request->validated());
    }

    // Login (Issue Token)
    public function login(LoginRequest $request)
    {

        return AuthService::login($request);
    }

    public function confirmOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|string|size:6',
        ]);
        return AuthService::confirmOtp($data);
    }

    // Logout (Revoke Token)
    public function logout(Request $request)
    {
        // return response()->json(['data' => $request]);
        $request->user()->currentAccessToken()->delete();

        return $this->sendResponse(null, __('messages.Logged_out_successfully'));
    }
}
