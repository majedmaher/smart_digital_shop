<?php

namespace App\Services;

use App\Enum\SocialProviderEnum;
use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialAuthService extends Controller
{
    /**
     * Get enabled social providers
     */
    public static function getEnabledProviders(): array
    {
        return collect(SocialProviderEnum::cases())
            ->filter(fn($provider) => $provider->isEnabled())
            ->map(fn($provider) => [
                'value' => $provider->value,
                'label' => $provider->getLabel(),
                'icon' => $provider->getIcon(),
                'color' => $provider->getColor(),
            ])
            ->toArray();
    }

    /**
     * Social login with access token
     */
    public static function socialLogin(array $data): \Illuminate\Http\JsonResponse
    {
        $provider = SocialProviderEnum::tryFrom($data['provider']);

        if (!$provider || !$provider->isEnabled()) {
            return BaseController::sendError(__('messages.provider_not_supported'), [], 422);
        }

        try {
            $socialUser = self::getSocialUser($provider, $data['access_token']);

            if (!$socialUser) {
                return BaseController::sendError(__('messages.invalid_access_token'), [], 422);
            }

            $user = self::createOrUpdateUser($socialUser, $provider, $data);

            // Set session timeout if provided
            if (isset($data['device_type']) && isset($data['device_id'])) {
                \App\Services\SessionTimeoutService::setUserSessionTimeout($user->id);
            }

            $token = $user->createToken('social_auth_token')->plainTextToken;

            $response = [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'points' => $user->points,
                    'wallet_balance' => $user->wallet_balance,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getPermissionNames(),
                    'is_admin' => $user->hasRole(\App\RoleEnum::ADMIN),
                ],
                'provider' => $provider->value,
                'login_method' => 'social',
            ];

            Log::info("Social login successful", [
                'provider' => $provider->value,
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return BaseController::sendResponse($response, __('messages.login_successfully'));
        } catch (\Exception $e) {
            Log::error("Social login failed", [
                'provider' => $data['provider'],
                'error' => $e->getMessage(),
            ]);
            return BaseController::sendError(__('messages.social_login_failed'), [], 500);
        }
    }

    /**
     * Get social user data based on provider
     */
    public static function getSocialUser(SocialProviderEnum $provider, string $accessToken): ?object
    {
        try {
            switch ($provider) {
                case SocialProviderEnum::GOOGLE:
                case SocialProviderEnum::FACEBOOK:
                    $driver = Socialite::driver($provider->value);
                    if (method_exists($driver, 'userFromToken')) {
                        return \call_user_func([$driver, 'userFromToken'], $accessToken);
                    }
                    // Fallback for providers that don't support userFromToken
                    return null;

                case SocialProviderEnum::APPLE:
                    return self::getAppleUser($accessToken);

                default:
                    return null;
            }
        } catch (\Exception $e) {
            Log::error("Failed to get social user", [
                'provider' => $provider->value,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get Apple user data
     */
    private static function getAppleUser(string $accessToken): ?object
    {
        try {
            // Apple uses JWT token, we need to decode it
            $response = Http::timeout(10)->get('https://appleid.apple.com/auth/keys');

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch Apple public keys');
            }

            $keys = $response->json()['keys'];

            // Decode JWT token (simplified - in production use proper JWT library)
            $tokenParts = explode('.', $accessToken);
            if (count($tokenParts) !== 3) {
                throw new \Exception('Invalid Apple token format');
            }

            $payload = json_decode(base64_decode($tokenParts[1]), true);

            return (object) [
                'id' => $payload['sub'] ?? null,
                'email' => $payload['email'] ?? null,
                'name' => $payload['name'] ?? 'Apple User',
                'nickname' => null,
            ];
        } catch (\Exception $e) {
            Log::error("Apple authentication failed", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create or update user from social data
     */
    private static function createOrUpdateUser(object $socialUser, SocialProviderEnum $provider, array $data): User
    {
        $email = $socialUser->email ?? $socialUser->id . '@' . $provider->value . '.local';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $socialUser->name ?? $socialUser->nickname ?? 'مستخدم',
                'password' => Hash::make(Str::random(12)),
                'phone' => null,
                'points' => 0,
                'wallet_balance' => 0,
            ]
        );

        // Update user info if needed
        if (!$user->wasRecentlyCreated) {
            $user->update([
                'name' => $socialUser->name ?? $socialUser->nickname ?? $user->name,
            ]);
        }

        return $user;
    }

    /**
     * Get redirect URL for OAuth flow
     */
    public static function redirect(string $provider): \Illuminate\Http\JsonResponse
    {
        $providerEnum = SocialProviderEnum::tryFrom($provider);

        if (!$providerEnum || !$providerEnum->isEnabled()) {
            return BaseController::sendError(__('messages.provider_not_supported'), [], 422);
        }

        try {
            $redirectUrl = Socialite::driver($provider)->redirect()->getTargetUrl();

            return BaseController::sendResponse([
                'redirect_url' => $redirectUrl,
                'provider' => $provider,
            ], __('messages.redirect_url_generated'));
        } catch (\Exception $e) {
            Log::error("Failed to generate redirect URL", [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }

    /**
     * Handle OAuth callback
     */
    public static function callback(string $provider): \Illuminate\Http\JsonResponse
    {
        $providerEnum = SocialProviderEnum::tryFrom($provider);

        if (!$providerEnum || !$providerEnum->isEnabled()) {
            return BaseController::sendError(__('messages.provider_not_supported'), [], 422);
        }

        try {
            $socialUser = Socialite::driver($provider)->user();

            $user = self::createOrUpdateUser($socialUser, $providerEnum, []);

            $token = $user->createToken('oauth_auth_token')->plainTextToken;

            $response = [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'points' => $user->points,
                    'wallet_balance' => $user->wallet_balance,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getPermissionNames(),
                    'is_admin' => $user->hasRole(\App\RoleEnum::ADMIN),
                ],
                'provider' => $provider,
                'login_method' => 'oauth',
            ];

            Log::info("OAuth callback successful", [
                'provider' => $provider,
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return BaseController::sendResponse($response, __('messages.login_successfully'));
        } catch (\Exception $e) {
            Log::error("OAuth callback failed", [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return BaseController::sendError(__('messages.login_failed'), [], 500);
        }
    }

    /**
     * Get social login statistics
     */
    public static function getStats(): \Illuminate\Http\JsonResponse
    {
        try {
            $totalSocialUsers = User::whereNotNull('email')
                ->where('email', 'like', '%@google.local')
                ->orWhere('email', 'like', '%@facebook.local')
                ->orWhere('email', 'like', '%@apple.local')
                ->count();

            $googleUsers = User::where('email', 'like', '%@google.local')->count();
            $facebookUsers = User::where('email', 'like', '%@facebook.local')->count();
            $appleUsers = User::where('email', 'like', '%@apple.local')->count();

            return BaseController::sendResponse([
                'total_social_users' => $totalSocialUsers,
                'providers' => [
                    'google' => $googleUsers,
                    'facebook' => $facebookUsers,
                    'apple' => $appleUsers,
                ],
                'enabled_providers' => self::getEnabledProviders(),
            ], __('messages.success'));
        } catch (\Exception $e) {
            Log::error("Failed to get social auth stats", [
                'error' => $e->getMessage(),
            ]);
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }
}
