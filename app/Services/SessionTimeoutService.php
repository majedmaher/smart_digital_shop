<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Services\SiteStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SessionTimeoutService
{
    private const CACHE_KEY_PREFIX = 'session_timeout_';
    private const DEFAULT_TIMEOUT_DEMO = 120; // 2 hours in minutes for demo
    private const DEFAULT_TIMEOUT_LIVE = 120; // 2 hours in minutes for live
    private const CACHE_TTL = 60 * 60 * 24; // 24 hours

    /**
     * Get current session timeout settings
     */
    public static function getTimeoutSettings(): JsonResponse
    {
        try {
            $demoTimeout = Cache::get('session_timeout_demo', self::DEFAULT_TIMEOUT_DEMO);
            $liveTimeout = Cache::get('session_timeout_live', self::DEFAULT_TIMEOUT_LIVE);
            $currentStatus = SiteStatusService::isDemoMode() ? 'demo' : 'live';
            $currentTimeout = $currentStatus === 'demo' ? $demoTimeout : $liveTimeout;

            return BaseController::sendResponse([
                'demo_timeout_minutes' => $demoTimeout,
                'live_timeout_minutes' => $liveTimeout,
                'current_status' => $currentStatus,
                'current_timeout_minutes' => $currentTimeout,
                'current_timeout_hours' => round($currentTimeout / 60, 2),
                'settings' => [
                    'demo' => [
                        'timeout_minutes' => $demoTimeout,
                        'timeout_hours' => round($demoTimeout / 60, 2),
                        'label' => 'موقع وهمي'
                    ],
                    'live' => [
                        'timeout_minutes' => $liveTimeout,
                        'timeout_hours' => round($liveTimeout / 60, 2),
                        'label' => 'موقع رسمي'
                    ]
                ]
            ], __('messages.success'));
        } catch (\Exception $e) {
            Log::error('Error getting session timeout settings: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Update session timeout for specific site status
     */
    public static function updateTimeout(string $siteStatus, int $timeoutMinutes): JsonResponse
    {
        try {
            // Validate site status
            if (!in_array($siteStatus, ['demo', 'live'])) {
                return BaseController::sendError(__('messages.invalid_status'), [], 400);
            }

            // Validate timeout (minimum 30 minutes, maximum 480 minutes = 8 hours)
            if ($timeoutMinutes < 30 || $timeoutMinutes > 480) {
                return BaseController::sendError(__('messages.timeout_range_invalid'), [], 400);
            }

            $cacheKey = "session_timeout_{$siteStatus}";
            Cache::put($cacheKey, $timeoutMinutes, self::CACHE_TTL);

            // Log the change
            Log::info("Session timeout updated for {$siteStatus} mode to {$timeoutMinutes} minutes");

            return BaseController::sendResponse([
                'site_status' => $siteStatus,
                'timeout_minutes' => $timeoutMinutes,
                'timeout_hours' => round($timeoutMinutes / 60, 2),
                'message' => __('messages.session_timeout_updated', [
                    'status' => $siteStatus === 'demo' ? 'الوضع الوهمي' : 'الوضع الرسمي',
                    'timeout' => $timeoutMinutes
                ])
            ], __('messages.success'));
        } catch (\Exception $e) {
            Log::error('Error updating session timeout: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Get current timeout based on site status
     */
    public static function getCurrentTimeout(): int
    {
        $isDemo = SiteStatusService::isDemoMode();
        $cacheKey = $isDemo ? 'session_timeout_demo' : 'session_timeout_live';
        $defaultTimeout = $isDemo ? self::DEFAULT_TIMEOUT_DEMO : self::DEFAULT_TIMEOUT_LIVE;

        return Cache::get($cacheKey, $defaultTimeout);
    }

    /**
     * Set session timeout for user
     */
    public static function setUserSessionTimeout(int $userId): void
    {
        $timeoutMinutes = self::getCurrentTimeout();
        $expiresAt = Carbon::now()->addMinutes($timeoutMinutes);

        $cacheKey = self::CACHE_KEY_PREFIX . $userId;
        Cache::put($cacheKey, $expiresAt, $timeoutMinutes * 60);

        Log::info("Session timeout set for user {$userId} until {$expiresAt}");
    }

    /**
     * Check if user session is expired
     */
    public static function isSessionExpired(int $userId): bool
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $userId;
        $expiresAt = Cache::get($cacheKey);

        if (!$expiresAt) {
            return true; // No session found, consider expired
        }

        return Carbon::now()->isAfter($expiresAt);
    }

    /**
     * Extend user session
     */
    public static function extendUserSession(int $userId): JsonResponse
    {
        try {
            if (self::isSessionExpired($userId)) {
                return BaseController::sendError(__('messages.session_expired'), [], 401);
            }

            self::setUserSessionTimeout($userId);

            return BaseController::sendResponse([
                'user_id' => $userId,
                'extended_until' => Carbon::now()->addMinutes(self::getCurrentTimeout())->toDateTimeString(),
                'timeout_minutes' => self::getCurrentTimeout()
            ], __('messages.session_extended'));
        } catch (\Exception $e) {
            Log::error('Error extending user session: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Get user session info
     */
    public static function getUserSessionInfo(int $userId): JsonResponse
    {
        try {
            $cacheKey = self::CACHE_KEY_PREFIX . $userId;
            $expiresAt = Cache::get($cacheKey);
            $isExpired = self::isSessionExpired($userId);
            $timeoutMinutes = self::getCurrentTimeout();

            if (!$expiresAt) {
                return BaseController::sendResponse([
                    'user_id' => $userId,
                    'has_session' => false,
                    'is_expired' => true,
                    'expires_at' => null,
                    'timeout_minutes' => $timeoutMinutes,
                    'message' => __('messages.no_active_session')
                ], __('messages.success'));
            }

            $remainingMinutes = Carbon::now()->diffInMinutes($expiresAt, false);
            $remainingMinutes = max(0, $remainingMinutes);

            return BaseController::sendResponse([
                'user_id' => $userId,
                'has_session' => true,
                'is_expired' => $isExpired,
                'expires_at' => $expiresAt->toDateTimeString(),
                'remaining_minutes' => $remainingMinutes,
                'remaining_hours' => round($remainingMinutes / 60, 2),
                'timeout_minutes' => $timeoutMinutes,
                'site_status' => SiteStatusService::isDemoMode() ? 'demo' : 'live'
            ], __('messages.success'));
        } catch (\Exception $e) {
            Log::error('Error getting user session info: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Clear user session
     */
    public static function clearUserSession(int $userId): JsonResponse
    {
        try {
            $cacheKey = self::CACHE_KEY_PREFIX . $userId;
            Cache::forget($cacheKey);

            Log::info("Session cleared for user {$userId}");

            return BaseController::sendResponse([
                'user_id' => $userId,
                'message' => __('messages.session_cleared')
            ], __('messages.success'));
        } catch (\Exception $e) {
            Log::error('Error clearing user session: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }
}
