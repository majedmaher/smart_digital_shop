<?php

namespace App\Services;

use App\Enum\SiteStatusEnum;
use App\Http\Controllers\API\BaseController;
use App\Services\SessionTimeoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SiteStatusService
{
    private const CACHE_KEY = 'site_status';
    private const CACHE_TTL = 60 * 60 * 24; // 24 hours

    /**
     * Get current site status
     */
    public static function getCurrentStatus(): JsonResponse
    {
        try {
            $status = Cache::get(self::CACHE_KEY, SiteStatusEnum::DEMO->value);
            $statusEnum = SiteStatusEnum::from($status);

            // Get current session timeout for this status
            $timeoutMinutes = SessionTimeoutService::getCurrentTimeout();

            return BaseController::sendResponse([
                'status' => $statusEnum->value,
                'label' => $statusEnum->getLabel(),
                'is_demo' => $statusEnum === SiteStatusEnum::DEMO,
                'is_live' => $statusEnum === SiteStatusEnum::LIVE,
                'session_timeout_minutes' => $timeoutMinutes,
                'session_timeout_hours' => round($timeoutMinutes / 60, 2),
            ], __('messages.success'));
        } catch (\Exception $e) {
            Log::error('Error getting site status: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Update site status
     */
    public static function updateStatus(string $status): JsonResponse
    {
        try {
            // Validate status
            if (!in_array($status, SiteStatusEnum::all())) {
                return BaseController::sendError(__('messages.invalid_status'), [], 400);
            }

            $statusEnum = SiteStatusEnum::from($status);

            // Update cache
            Cache::put(self::CACHE_KEY, $statusEnum->value, self::CACHE_TTL);

            // Log the change
            Log::info("Site status changed to: {$statusEnum->getLabel()} ({$statusEnum->value})");

            // Get current session timeout for this status
            $timeoutMinutes = SessionTimeoutService::getCurrentTimeout();

            return BaseController::sendResponse([
                'status' => $statusEnum->value,
                'label' => $statusEnum->getLabel(),
                'is_demo' => $statusEnum === SiteStatusEnum::DEMO,
                'is_live' => $statusEnum === SiteStatusEnum::LIVE,
                'session_timeout_minutes' => $timeoutMinutes,
                'session_timeout_hours' => round($timeoutMinutes / 60, 2),
                'message' => __('messages.site_status_updated', ['status' => $statusEnum->getLabel()])
            ], __('messages.success'));
        } catch (\Exception $e) {
            Log::error('Error updating site status: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Check if site is in demo mode
     */
    public static function isDemoMode(): bool
    {
        $status = Cache::get(self::CACHE_KEY, SiteStatusEnum::DEMO->value);
        return SiteStatusEnum::from($status) === SiteStatusEnum::DEMO;
    }

    /**
     * Check if site is live
     */
    public static function isLiveMode(): bool
    {
        $status = Cache::get(self::CACHE_KEY, SiteStatusEnum::DEMO->value);
        return SiteStatusEnum::from($status) === SiteStatusEnum::LIVE;
    }

    /**
     * Get available statuses
     */
    public static function getAvailableStatuses(): JsonResponse
    {
        try {
            $statuses = [];
            foreach (SiteStatusEnum::cases() as $status) {
                $statuses[] = [
                    'value' => $status->value,
                    'label' => $status->getLabel(),
                ];
            }

            return BaseController::sendResponse($statuses, __('messages.success'));
        } catch (\Exception $e) {
            Log::error('Error getting available statuses: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }
}
