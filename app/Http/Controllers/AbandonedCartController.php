<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\SendAbandonedCartNotificationRequest;
use App\Services\AbandonedCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbandonedCartController extends Controller
{
    /**
     * Get all abandoned carts
     */
    public function getAbandonedCarts(): JsonResponse
    {
        return AbandonedCartService::getAbandonedCarts();
    }

    /**
     * Send notification to specific abandoned cart
     */
    public function sendNotification(SendAbandonedCartNotificationRequest $request): JsonResponse
    {
        $data = $request->validated();
        return AbandonedCartService::sendAbandonedCartNotification(
            $data['order_id'],
            $data['notification_type'] ?? 'reminder'
        );
    }

    /**
     * Send bulk notifications to all abandoned carts
     */
    public function sendBulkNotifications(Request $request): JsonResponse
    {
        $notificationType = $request->input('notification_type', 'reminder');
        return AbandonedCartService::sendBulkNotifications($notificationType);
    }

    /**
     * Get abandoned cart statistics
     */
    public function getStats(): JsonResponse
    {
        return AbandonedCartService::getAbandonedCartStats();
    }

    /**
     * Mark order as recovered
     */
    public function markAsRecovered(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id'
        ]);

        return AbandonedCartService::markAsRecovered($request->order_id);
    }

    /**
     * Trigger abandoned cart processing job manually
     */
    public function triggerProcessing(): JsonResponse
    {
        try {
            \App\Jobs\ProcessAbandonedCartNotifications::dispatch();

            return BaseController::sendResponse([
                'message' => __('messages.abandoned_cart_processing_triggered'),
                'job_dispatched' => true,
            ], __('messages.success'));
        } catch (\Exception $e) {
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Get abandoned cart settings
     */
    public function getSettings(): JsonResponse
    {
        try {
            $settings = [
                'abandoned_hours_threshold' => 8,
                'notification_delays' => [
                    'reminder' => 1, // ساعة واحدة
                    'urgent' => 24,  // 24 ساعة
                    'final' => 72,  // 72 ساعة
                ],
                'discount_values' => [
                    'reminder' => 5,  // 5%
                    'urgent' => 10,   // 10%
                    'final' => 15,   // 15%
                ],
                'coupon_expiry_days' => 7,
                'max_notifications_per_cart' => 3,
            ];

            return BaseController::sendResponse($settings, __('messages.success'));
        } catch (\Exception $e) {
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }
}
