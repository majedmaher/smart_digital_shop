<?php

namespace App\Http\Controllers;

use App\Http\Requests\SocialAuthRequest;
use App\Services\SocialAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialAuthController extends Controller
{
    /**
     * Get enabled social providers
     */
    public function getProviders(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SocialAuthService::getEnabledProviders(),
            'message' => __('messages.success')
        ]);
    }

    /**
     * Social login with access token
     */
    public function socialLogin(SocialAuthRequest $request): JsonResponse
    {
        return SocialAuthService::socialLogin($request->validated());
    }

    /**
     * Get OAuth redirect URL
     */
    public function redirect(string $provider): JsonResponse
    {
        return SocialAuthService::redirect($provider);
    }

    /**
     * Handle OAuth callback
     */
    public function callback(string $provider): JsonResponse
    {
        return SocialAuthService::callback($provider);
    }

    /**
     * Get social login statistics
     */
    public function getStats(): JsonResponse
    {
        return SocialAuthService::getStats();
    }

    /**
     * Link social account to existing user
     */
    public function linkAccount(SocialAuthRequest $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $data = $request->validated();
            $user = \App\Models\User::find($data['user_id']);

            // Check if user is authenticated and has permission
            $authenticatedUser = auth()->user();
            if (!$authenticatedUser || $authenticatedUser->id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.do_not_have_permission')
                ], 403);
            }

            // Get social user data
            $provider = \App\Enum\SocialProviderEnum::tryFrom($data['provider']);
            $socialUser = SocialAuthService::getSocialUser($provider, $data['access_token']);

            if (!$socialUser) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.invalid_access_token')
                ], 422);
            }

            // Update user with social provider info
            $user->update([
                'social_provider' => $provider->value,
                'social_id' => $socialUser->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('messages.social_account_linked'),
                'data' => [
                    'user_id' => $user->id,
                    'provider' => $provider->value,
                    'social_id' => $socialUser->id,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.error_occurred')
            ], 500);
        }
    }

    /**
     * Unlink social account
     */
    public function unlinkAccount(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => 'required|string|in:google,facebook,apple',
        ]);

        try {
            $authenticatedUser = auth()->user();
            if (!$authenticatedUser) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.unauthorized')
                ], 401);
            }

            $provider = $request->provider;

            $authenticatedUser->update([
                'social_provider' => null,
                'social_id' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('messages.social_account_unlinked'),
                'data' => [
                    'user_id' => $authenticatedUser->id,
                    'provider' => $provider,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.error_occurred')
            ], 500);
        }
    }
}
