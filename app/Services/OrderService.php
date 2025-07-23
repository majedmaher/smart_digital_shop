<?php

namespace App\Services;

use App\Enum\CouponTypeEnum;
use App\Enum\ShippingMethodPayment;
use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponUserUsage;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService extends Controller
{
    static function store($data): JsonResponse
    {
        $cart = $data['cart'];

        if (!is_array($cart) || empty($cart)) {
            return BaseController::sendError(__('messages.cart_empty'), [], 400);
        }

        DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id' => auth()->id(),
                'status' => 'pending',
                'total_price' => 0,
            ]);

            $totalPrice = 0;

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
                // $method = $product->shipping_payment instanceof \BackedEnum ? $product->shipping_payment->value : $product->shipping_payment;
                $shipping = $item['shipping_data'] ?? [];
                // return response()->json([$method, ShippingMethodPayment::CODE]);
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
                $order->items()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'total_price' => $quantity * $product->price,
                    'shipping_method' => $method,
                    'shipping_data' => json_encode($shipping),
                ]);

                $totalPrice += $quantity * $product->price;
            }

            if (!empty($data['coupon_id']) && isset($data['coupon_id'])) {
                $coupon = Coupon::findOrFail($data['coupon_id']);

                if (!$coupon || !$coupon->active) {
                    return BaseController::sendError(__('messages.invalid_coupon'), [], 422);
                }

                if ($coupon->usage_limit !== null && $coupon->used >= $coupon->usage_limit) {
                    return BaseController::sendError(__('messages.used_maximum_coupon'), [], 422);
                }

                if (
                    ($coupon->expires_from && now()->lt($coupon->expires_from)) ||
                    ($coupon->expires_at && now()->gt($coupon->expires_at))
                ) {
                    return BaseController::sendError(__('messages.coupon_expired'), [], 422);
                }

                // حساب الخصم حسب النوع
                if ($coupon->type === CouponTypeEnum::FIXED->value) {
                    $discount = $coupon->value;
                } elseif ($coupon->type === CouponTypeEnum::PERCENT->value) {
                    $discount = $totalPrice * ($coupon->value / 100);
                } else {
                    $discount = 0;
                }

                $totalPrice = max(0, $totalPrice - $discount); // التأكد ألا يكون أقل من صفر
                $coupon->used = $coupon->used + 1;
                $coupon->update();
            }
            $order->update(['total_price' => $totalPrice, 'coupon_id' => $coupon->id ?? null]);

            $responseData = [
                'order_id' => $order->id,
                'total_price' => $totalPrice,
            ];
            DB::commit();
            return BaseController::sendResponse($responseData, __('messages.order_created_successfully'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseController::sendError(__('messages.something_went_wrong'), [$th->getMessage()], 500);
        }
    }
}
