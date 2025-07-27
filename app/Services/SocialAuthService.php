<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialAuthService extends Controller
{
    private static array $allowedProviders = ['google', 'facebook'];
    // route: POST /api/social-login
    static function socialLogin($data)
    {

        if (!in_array($data['provider'], self::$allowedProviders)) {
            return BaseController::sendError(__('messages.provider_not_supported'), [], 422);
        }

        try {
            $socialUser = Socialite::driver($data['provider'])
                ->stateless()
                ->userFromToken($data['access_token']);

            if (!$socialUser->getEmail()) {
                return BaseController::sendError(__('messages.email_not_provided'), [], 422);
            }
            $user = User::firstOrCreate(
                ['email' => $socialUser->getEmail()],
                [
                    'name'     => $socialUser->getName() ?? $socialUser->getNickname() ?? 'مستخدم',
                    'password' => Hash::make(Str::random(12)),
                ]
            );


            $token = $user->createToken('auth_token')->plainTextToken;
            $response = ['token' => $token, 'user' => $user];

            return BaseController::sendResponse($response, __('messages.login_successfully'));
        } catch (\Exception $e) {
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }

    static function redirect($provider)
    {
        try {
            if (!in_array($provider, self::$allowedProviders)) {
                return BaseController::sendError(__('messages.provider_not_supported'), [], 422);
            }
            return Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();
            // return Socialite::driver($provider)->stateless()->redirect();
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }

    static function callback($provider)
    {
        if (!in_array($provider, self::$allowedProviders)) {
            return BaseController::sendError(__('messages.provider_not_supported'), [], 422);
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();

            $user = User::firstOrCreate(
                ['email' => $socialUser->getEmail()],
                [
                    'name'     => $socialUser->getName() ?? $socialUser->getNickname() ?? 'مستخدم',
                    'password' => Hash::make(Str::random(12)),
                ]

            );


            $token = $user->createToken('auth_token')->plainTextToken;
            $response = [
                'token' => $token,
                'user' => $user->only(['id', 'name', 'email'])
            ];

            return BaseController::sendResponse($response, __('messages.login_successfully'));
        } catch (\Exception $e) {
            return BaseController::sendError((__('messages.login_failed')), [$e->getMessage()], 500);
        }
    }
}
