<?php

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthService extends Controller
{
    protected array $allowedProviders = ['google', 'facebook'];
    static function redirect($provider)
    {
        if (!in_array($provider, $this->allowedProviders)) {
            return response()->json(['message' => 'Provider غير مدعوم'], 422);
        }

        return Socialite::driver($provider)->redirect()->getTargetUrl();
    }

    static function callback($provider)
    {
        if (!in_array($provider, $this->allowedProviders)) {
            return response()->json(['message' => 'Provider غير مدعوم'], 422);
        }

        try {
            $socialUser = Socialite::driver($provider)->user();

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
            return BaseController::sendError((__('messages.login_failed')), [], 500);
        }
    }
}
