<?php

namespace App\Services;

use App\Enum\OrderStatusEnum;
use App\Enum\PaymentCurrencyEnum;
use App\Enum\PaymentProviderEnum;
use App\Enum\PaymentStatusEnum;
use Illuminate\Http\JsonResponse;

use App\Http\Controllers\API\BaseController;
use App\Jobs\SendCodeAfterPayment;
use App\Models\Order;
use App\Models\PointsRedemption;
use App\Models\WalletTransaction;
use App\Notifications\PaymentSuccessNotification;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public static function redeem($currency): JsonResponse
    {
        try {
            $user = auth()->user();

            // نتحقق عنده على الأقل 1000 نقطة
            if ($user->points < 1000) {
                return BaseController::sendError(__('messages.your_balance_less_than_the_minimum_transfer_amount'), [], 422);
            }

            return DB::transaction(function () use ($user, $currency) {
                // نحسب كم بلوك من 1000 نقطة عنده
                $blocks = floor($user->points / 1000);

                // النقاط اللي رح تتحول
                $pointsToRedeem = $blocks * 1000;

                $totalAmount = $blocks * 0.5;

                // نخصم النقاط
                $user->decrement('points', $pointsToRedeem);
                // إضافة الرصيد للمحفظة
                $user->increment('wallet_balance', $totalAmount);


                // نوثق العملية
                PointsRedemption::create([
                    'user_id' => $user->id,
                    'points_redeemed' => $pointsToRedeem,
                    'amount' => $totalAmount,
                ]);
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'deposit',
                    'amount' => $totalAmount,
                    'description' => 'Redeemed points: ' . $pointsToRedeem,
                ]);

                $response = [
                    'points_redeemed' => $pointsToRedeem,
                    'points_balance' => $user->fresh()->points,
                    'amount' => currencyConverter($totalAmount, $currency),
                    'wallet_balance' => currencyConverter($user->fresh()->wallet_balance, $currency),
                ];
                return BaseController::sendResponse($response, __('messages.points_have_been_transferred_successfully'));
            });
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }

    public static function payUsingWallet($order_id): JsonResponse
    {
        try {
            $order = Order::with('items.product')->find($order_id);
            $user = auth()->user();

            if (!$order) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.order')]), [], 404);
            }

            if ($order->status === OrderStatusEnum::PAID->value) {
                return BaseController::sendResponse(['order_id' => $order->id], __('messages.order_paid'));
            }

            // ✅ التحقق من الأكواد أولاً
            if ($error = OrderService::validateCodeAvailability($order->items->toArray())) {
                return $error; // رجوع فوري لو فيه خلل
            }

            $orderTotal = $order->total_price;
            if ($user->wallet_balance < $orderTotal) {
                return BaseController::sendError(__('messages.not_enough_balance_in_wallet'), [], 422);
            }

            DB::transaction(function () use ($user, $orderTotal, $order) {
                // خصم من المحفظة
                $user->decrement('wallet_balance', $orderTotal);

                // توثيق العملية
                $walletTransaction = WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'withdraw',
                    'amount' => $orderTotal,
                    'description' => 'Payment for order #' . $order->id,
                ]);

                $order->payments()->create([
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'payment_provider' => PaymentProviderEnum::WALLET->value,
                    'reference' => $walletTransaction->id, // transaction ID
                    'payment_intention_id' => $obj['payment_key_claims']['next_payment_intention'] ?? null,
                    'currency' => PaymentCurrencyEnum::SAR->value,
                    'amount_cents' => $order->total_price * 100,
                    'status' => PaymentStatusEnum::PAID->value,
                    'paid_at' => now(),
                    'raw_response' => null,
                ]);


                // تحديث حالة الطلب
                $order->update(['status' => OrderStatusEnum::PAID->value]);
                if ($user && $user->email) {
                    $user->notify(new PaymentSuccessNotification($order));
                }

                SendCodeAfterPayment::dispatch($order);
            });
            return BaseController::sendResponse(['order_id' => $order->id, 'transaction_id' => $walletTransaction->id], __('messages.payment_confirmed'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [$th->getMessage()], 500);
        }
    }
}
