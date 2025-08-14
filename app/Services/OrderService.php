<?php

namespace App\Services;

use App\Enum\CouponTypeEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\ShippingMethodPayment;
use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderService extends Controller
{
    public static function orderStatistics(): JsonResponse
    {
        try {
            $today = now()->startOfDay();
            $total = Order::where('status', OrderStatusEnum::PAID->value)->sum('total_price');
            $today_total = Order::where('status', OrderStatusEnum::PAID->value)
                ->whereDate('created_at', $today)
                ->sum('total_price');
            $response = [
                'total' => $total,
                'today' => $today_total,
            ];
            return BaseController::sendResponse($response, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }

    public static function ordersCountStats(): JsonResponse
    {
        try {
            $today = now()->startOfDay();
            $weekAgo = now()->subDays(7);
            $monthAgo = now()->subDays(30);

            $stats = Order::selectRaw("
        SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as today_orders_count,
        SUM(CASE WHEN DATE(created_at) = ? THEN total_price ELSE 0 END) as today_orders_amount,
        SUM(CASE WHEN status = 'paid' AND DATE(created_at) = ? THEN 1 ELSE 0 END) as today_paid_orders_count,
        SUM(CASE WHEN status = 'paid' AND DATE(created_at) = ? THEN total_price ELSE 0 END) as today_paid_orders_amount,

        SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as week_orders_count,
        SUM(CASE WHEN created_at >= ? THEN total_price ELSE 0 END) as week_orders_amount,
        SUM(CASE WHEN status = 'paid' AND created_at >= ? THEN 1 ELSE 0 END) as week_paid_orders_count,
        SUM(CASE WHEN status = 'paid' AND created_at >= ? THEN total_price ELSE 0 END) as week_paid_orders_amount,

        SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as month_orders_count,
        SUM(CASE WHEN created_at >= ? THEN total_price ELSE 0 END) as month_orders_amount,
        SUM(CASE WHEN status = 'paid' AND created_at >= ? THEN 1 ELSE 0 END) as month_paid_orders_count,
        SUM(CASE WHEN status = 'paid' AND created_at >= ? THEN total_price ELSE 0 END) as month_paid_orders_amount
    ", [
                $today,
                $today,
                $today,
                $today,
                $weekAgo,
                $weekAgo,
                $weekAgo,
                $weekAgo,
                $monthAgo,
                $monthAgo,
                $monthAgo,
                $monthAgo
            ])->first();

            $response = [
                'today' => [
                    'orders_count' => (int) $stats->today_orders_count,
                    'orders_amount' => (float) $stats->today_orders_amount,
                    'paid_orders_count' => (int) $stats->today_paid_orders_count,
                    'paid_orders_amount' => (float) $stats->today_paid_orders_amount,
                ],
                'last_week' => [
                    'orders_count' => (int) $stats->week_orders_count,
                    'orders_amount' => (float) $stats->week_orders_amount,
                    'paid_orders_count' => (int) $stats->week_paid_orders_count,
                    'paid_orders_amount' => (float) $stats->week_paid_orders_amount,
                ],
                'last_month' => [
                    'orders_count' => (int) $stats->month_orders_count,
                    'orders_amount' => (float) $stats->month_orders_amount,
                    'paid_orders_count' => (int) $stats->month_paid_orders_count,
                    'paid_orders_amount' => (float) $stats->month_paid_orders_amount,
                ]
            ];
            return BaseController::sendResponse($response, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }


    public static function store($data, $currency): JsonResponse
    {

        $cart = $data['cart'];
        $couponCode = $data['coupon_code'] ?? null;

        if (!is_array($cart) || empty($cart)) {
            return BaseController::sendError(__('messages.cart_empty'), [], 400);
        }

        if ($error = OrderService::validateCodeAvailability($cart)) {
            return $error;
        }
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $order = Order::create([
                'user_id' => $user->id,
                'status' => OrderStatusEnum::PENDING->value,
                'total_price' => 0,
            ]);

            $totalPrice = 0;
            $discount = 0;
            $discountedItems = [];

            foreach ($cart as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = intval($item['quantity']);

                if ($quantity < 1) {
                    return BaseController::sendError(__('messages.quantity_is_low', ['quantity' => 1]), [], 400);
                }
                if ($codes_count = $product->codes->count() < $quantity && $product->shipping_payment === ShippingMethodPayment::CODE->value) {
                    return BaseController::sendError(__('messages.quantity_is_high', ['quantity' => $codes_count]), [], 400);
                }

                $method = $product->shipping_payment;
                $shipping = $item['shipping_data'] ?? [];

                switch ($method) {
                    case ShippingMethodPayment::CODE->value:
                        if (empty($shipping['email']) || !filter_var($shipping['email'], FILTER_VALIDATE_EMAIL)) {
                            return BaseController::sendError(__('messages.enter_valid_data_to_product'), [], 400);
                        }
                        break;

                    case ShippingMethodPayment::ACCOUNT_ID->value:
                        if (empty($shipping['account_id'])) {
                            return BaseController::sendError(__('messages.enter_account_id'), [], 400);
                        }
                        break;

                    case ShippingMethodPayment::MULTI_ID->value:
                        if (empty($shipping['account_1']) || empty($shipping['account_2'])) {
                            return BaseController::sendError(__('messages.enter_both_account_id'), [], 400);
                        }
                        break;

                    case ShippingMethodPayment::ACCESS->value:
                        $requiredFields = ['login_method', 'email_phone', 'password', 'account_id'];
                        foreach ($requiredFields as $field) {
                            if (empty($shipping[$field])) {
                                return BaseController::sendError(__('messages.field_required'), [], 400);
                            }
                        }
                        break;

                    default:
                        return BaseController::sendError(__('messages.shipping_method_unknown'), [], 400);
                }

                // ✅ إضافة مؤقتة لحساب السعر قبل الخصم
                $item['price'] = $product->price;
                $item['product_id'] = $product->id;
                $item['shipping_data'] = $shipping;
                $discountedItems[] = $item;

                $totalPrice += $quantity * $product->price;
            }

            // ✅ التحقق من الكوبون إن وُجد
            $appliedCoupon = null;
            if ($couponCode) {
                $couponResult = CouponService::validateCoupon($couponCode, $user, $discountedItems);

                if (isset($couponResult[0]) && is_string($couponResult[0])) {
                    return BaseController::sendError($couponResult[0], [], $couponResult[1]);
                }

                $appliedCoupon = $couponResult['coupon'];
                $discountedItems = CouponService::distributeDiscount($couponResult);

                $discount = $appliedCoupon->type === CouponTypeEnum::FIXED->value
                    ? $appliedCoupon->value
                    : round(($appliedCoupon->value / 100) * $couponResult['eligible_total'], 2);

                $totalPrice = array_reduce($discountedItems, function ($carry, $item) {
                    return $carry + $item['final_price'];
                }, 0.0);

                $appliedCoupon->increment('used');
            }


            // ✅ إنشاء عناصر الطلب
            foreach ($discountedItems as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'discount' => $item['discount'] ?? 0,
                    'total' => $item['final_price'] ?? ($item['price'] * $item['quantity']),
                    'shipping_method' => $product->shipping_payment,
                    'shipping_data' => json_encode($item['shipping_data']),
                ]);
            }

            // ✅ تحديث الطلب بالسعر النهائي
            $order->update([
                'total_price' => $totalPrice,
                'coupon_id' => $appliedCoupon?->id,
                'discount' => $discount,
            ]);

            DB::commit();

            $responseData = [
                'order_id' => $order->id,
                'total_price' => $totalPrice ? currencyConverter($totalPrice, $currency, 2) : null,
                'discount' => $discount ? currencyConverter($discount, $currency, 2) : null,
            ];
            return BaseController::sendResponse($responseData, __('messages.order_created_successfully'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseController::sendError(__('messages.something_went_wrong'), [$th->getMessage()], 500);
        }
    }

    public static function validateCodeAvailability(array $items): ?\Illuminate\Http\JsonResponse
    {
        foreach ($items as $item) {
            $product = Product::with(['codes' => function ($query) {
                $query->whereNull('used_at');
            }])->find($item['product_id']);
            if (!$product) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.product')]), [], 404);
            }

            if ($product->shipping_payment === ShippingMethodPayment::CODE->value) {
                $requestedQuantity = collect($items)
                    ->where('product_id', $product->id)
                    ->sum('quantity');

                $availableCodesCount = $product->codes->count();

                if ($availableCodesCount < $requestedQuantity) {
                    return BaseController::sendError(__('messages.quantity_is_high', ['quantity' => $availableCodesCount]), [], 400);
                }
            }
        }

        return null; // لا يوجد أخطاء
    }
}
