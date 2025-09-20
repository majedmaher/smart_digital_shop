<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Models\Order;
use App\Models\User;
use App\Enum\OrderStatusEnum;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AbandonedCartService
{
    private const ABANDONED_HOURS = 8; // 8 ساعات
    private const NOTIFICATION_DELAYS = [1, 24, 72]; // ساعات الإشعارات المتدرجة

    /**
     * Get abandoned carts (orders pending for more than 8 hours)
     */
    public static function getAbandonedCarts(): JsonResponse
    {
        try {
            $abandonedTime = Carbon::now()->subHours(self::ABANDONED_HOURS);

            $abandonedCarts = Order::with(['user', 'items.product', 'coupon'])
                ->where('status', OrderStatusEnum::PENDING->value)
                ->where('created_at', '<', $abandonedTime)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'user_id' => $order->user_id,
                        'user_name' => $order->user->name,
                        'user_email' => $order->user->email,
                        'user_phone' => $order->user->phone,
                        'total_price' => $order->total_price,
                        'discount' => $order->discount,
                        'items_count' => $order->items->count(),
                        'items' => $order->items->map(function ($item) {
                            return [
                                'product_name' => $item->product->title,
                                'quantity' => $item->quantity,
                                'price' => $item->price,
                                'total' => $item->total,
                            ];
                        }),
                        'coupon_code' => $order->coupon?->code,
                        'created_at' => $order->created_at,
                        'abandoned_hours' => Carbon::now()->diffInHours($order->created_at),
                        'last_notification_sent' => $order->last_abandoned_notification_at,
                        'notifications_count' => $order->abandoned_notifications_count ?? 0,
                    ];
                });

            return BaseController::sendResponse([
                'abandoned_carts' => $abandonedCarts,
                'total_count' => $abandonedCarts->count(),
                'abandoned_hours_threshold' => self::ABANDONED_HOURS,
                'notification_delays' => self::NOTIFICATION_DELAYS,
            ], __('messages.success'));
        } catch (\Exception $e) {
            Log::error('Error getting abandoned carts: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Send notification to abandoned cart owner
     */
    public static function sendAbandonedCartNotification(int $orderId, string $type = 'reminder'): JsonResponse
    {
        try {
            $order = Order::with(['user', 'items.product', 'coupon'])->find($orderId);

            if (!$order) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.order')]), [], 404);
            }

            if ($order->status !== OrderStatusEnum::PENDING->value) {
                return BaseController::sendError(__('messages.order_not_pending'), [], 400);
            }

            $abandonedHours = Carbon::now()->diffInHours($order->created_at);
            if ($abandonedHours < self::ABANDONED_HOURS) {
                return BaseController::sendError(__('messages.order_not_abandoned'), [], 400);
            }

            // إنشاء كوبون خصم للسلة المتروكة
            $discountCoupon = self::createAbandonedCartCoupon($order, $type);

            // إرسال الإشعار
            $order->user->notify(new \App\Notifications\AbandonedCartNotification($order, $discountCoupon, $type));

            // تحديث عداد الإشعارات
            $order->increment('abandoned_notifications_count');
            $order->update(['last_abandoned_notification_at' => now()]);

            Log::info("Abandoned cart notification sent for order {$orderId} to user {$order->user->email}");

            return BaseController::sendResponse([
                'order_id' => $orderId,
                'user_email' => $order->user->email,
                'notification_type' => $type,
                'discount_coupon' => $discountCoupon ? [
                    'code' => $discountCoupon->code,
                    'discount_type' => $discountCoupon->type,
                    'discount_value' => $discountCoupon->value,
                    'expires_at' => $discountCoupon->expires_at,
                ] : null,
                'abandoned_hours' => $abandonedHours,
                'notifications_sent' => $order->fresh()->abandoned_notifications_count,
            ], __('messages.abandoned_cart_notification_sent'));
        } catch (\Exception $e) {
            Log::error('Error sending abandoned cart notification: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Send bulk notifications to all abandoned carts
     */
    public static function sendBulkNotifications(string $type = 'reminder'): JsonResponse
    {
        try {
            $abandonedTime = Carbon::now()->subHours(self::ABANDONED_HOURS);

            $abandonedOrders = Order::with(['user', 'items.product', 'coupon'])
                ->where('status', OrderStatusEnum::PENDING->value)
                ->where('created_at', '<', $abandonedTime)
                ->get();

            $sentCount = 0;
            $errors = [];

            foreach ($abandonedOrders as $order) {
                try {
                    $result = self::sendAbandonedCartNotification($order->id, $type);
                    if ($result->getData()->success) {
                        $sentCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Order {$order->id}: " . $e->getMessage();
                }
            }

            return BaseController::sendResponse([
                'total_abandoned_carts' => $abandonedOrders->count(),
                'notifications_sent' => $sentCount,
                'errors' => $errors,
                'notification_type' => $type,
            ], __('messages.bulk_notifications_sent'));
        } catch (\Exception $e) {
            Log::error('Error sending bulk notifications: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Create discount coupon for abandoned cart
     */
    private static function createAbandonedCartCoupon(Order $order, string $type): ?\App\Models\Coupon
    {
        try {
            $discountValue = self::getDiscountValue($type, $order->total_price);
            if (!$discountValue) {
                return null;
            }

            $couponCode = 'ABANDONED_' . strtoupper($type) . '_' . $order->id . '_' . time();

            $coupon = \App\Models\Coupon::create([
                'code' => $couponCode,
                'type' => 'percentage', // نسبة مئوية
                'value' => $discountValue,
                'usage_limit' => 1, // استخدام واحد فقط
                'used' => 0,
                'active' => true,
                'expires_at' => Carbon::now()->addDays(7), // صالح لمدة 7 أيام
                'description' => __('messages.abandoned_cart_coupon_description', [
                    'type' => $type,
                    'order_id' => $order->id
                ]),
            ]);

            // ربط الكوبون بالمستخدم فقط
            $coupon->allowedUsers()->attach($order->user_id);

            return $coupon;
        } catch (\Exception $e) {
            Log::error('Error creating abandoned cart coupon: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get discount value based on notification type and order total
     */
    private static function getDiscountValue(string $type, float $orderTotal): ?float
    {
        $discounts = [
            'reminder' => 5, // 5% خصم
            'urgent' => 10,  // 10% خصم
            'final' => 15,  // 15% خصم
        ];

        return $discounts[$type] ?? null;
    }

    /**
     * Get abandoned cart statistics
     */
    public static function getAbandonedCartStats(): JsonResponse
    {
        try {
            $abandonedTime = Carbon::now()->subHours(self::ABANDONED_HOURS);

            $totalAbandoned = Order::where('status', OrderStatusEnum::PENDING->value)
                ->where('created_at', '<', $abandonedTime)
                ->count();

            $totalValue = Order::where('status', OrderStatusEnum::PENDING->value)
                ->where('created_at', '<', $abandonedTime)
                ->sum('total_price');

            $notificationsSent = Order::where('status', OrderStatusEnum::PENDING->value)
                ->where('created_at', '<', $abandonedTime)
                ->whereNotNull('abandoned_notifications_count')
                ->sum('abandoned_notifications_count');

            $recoveredCarts = Order::where('status', OrderStatusEnum::PAID->value)
                ->whereNotNull('abandoned_notifications_count')
                ->where('abandoned_notifications_count', '>', 0)
                ->count();

            return BaseController::sendResponse([
                'total_abandoned_carts' => $totalAbandoned,
                'total_abandoned_value' => $totalValue,
                'notifications_sent' => $notificationsSent,
                'recovered_carts' => $recoveredCarts,
                'recovery_rate' => $totalAbandoned > 0 ? round(($recoveredCarts / $totalAbandoned) * 100, 2) : 0,
                'abandoned_hours_threshold' => self::ABANDONED_HOURS,
            ], __('messages.success'));
        } catch (\Exception $e) {
            Log::error('Error getting abandoned cart stats: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Mark order as recovered (converted to paid)
     */
    public static function markAsRecovered(int $orderId): JsonResponse
    {
        try {
            $order = Order::find($orderId);

            if (!$order) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.order')]), [], 404);
            }

            $order->update([
                'recovered_at' => now(),
                'recovery_source' => 'abandoned_cart_notification'
            ]);

            Log::info("Order {$orderId} marked as recovered from abandoned cart");

            return BaseController::sendResponse([
                'order_id' => $orderId,
                'recovered_at' => $order->recovered_at,
                'recovery_source' => $order->recovery_source,
            ], __('messages.order_marked_as_recovered'));
        } catch (\Exception $e) {
            Log::error('Error marking order as recovered: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }
}
