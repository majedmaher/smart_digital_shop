<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MaintenanceService
{
    private const CACHE_KEY = 'maintenance_mode';
    private const CACHE_TTL = 60 * 60 * 24 * 365; // 1 year

    /**
     * Get current maintenance mode status
     */
    public static function getStatus(): JsonResponse
    {
        try {
            $isMaintenance = self::isMaintenanceMode();
            
            return BaseController::sendResponse([
                'is_maintenance' => $isMaintenance,
                'status' => $isMaintenance ? 'maintenance' : 'active',
                'message' => $isMaintenance ? 'الموقع في وضع الصيانة' : 'الموقع يعمل بشكل طبيعي'
            ], __('messages.success'));
        } catch (\Exception $e) {
            Log::error('Error getting maintenance status: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Enable maintenance mode
     */
    public static function enable(): JsonResponse
    {
        try {
            // Store in cache
            Cache::put(self::CACHE_KEY, true, self::CACHE_TTL);
            
            Log::info('Maintenance mode enabled');
            
            return BaseController::sendResponse([
                'is_maintenance' => true,
                'status' => 'maintenance',
                'message' => 'تم تفعيل وضع الصيانة'
            ], __('messages.success'));
        } catch (\Exception $e) {
            Log::error('Error enabling maintenance mode: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Disable maintenance mode
     */
    public static function disable(): JsonResponse
    {
        try {
            // Remove from cache
            Cache::put(self::CACHE_KEY, false, self::CACHE_TTL);
            
            Log::info('Maintenance mode disabled');
            
            return BaseController::sendResponse([
                'is_maintenance' => false,
                'status' => 'active',
                'message' => 'تم إيقاف وضع الصيانة'
            ], __('messages.success'));
        } catch (\Exception $e) {
            Log::error('Error disabling maintenance mode: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Toggle maintenance mode
     */
    public static function toggle(): JsonResponse
    {
        try {
            $isMaintenance = self::isMaintenanceMode();
            
            if ($isMaintenance) {
                return self::disable();
            } else {
                return self::enable();
            }
        } catch (\Exception $e) {
            Log::error('Error toggling maintenance mode: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Check if maintenance mode is active
     */
    public static function isMaintenanceMode(): bool
    {
        return Cache::get(self::CACHE_KEY, false);
    }
}

