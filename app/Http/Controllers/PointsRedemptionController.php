<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Models\PointsRedemption;
use Illuminate\Support\Facades\DB;

class PointsRedemptionController extends Controller
{
    public function redeem()
    {
        try {
            $user = auth()->user();

            // نتحقق عنده على الأقل 1000 نقطة
            if ($user->points < 1000) {
                return BaseController::sendError(__('messages.your_balance_less_than_the_minimum_transfer_amount'), [], 422);
            }

            return DB::transaction(function () use ($user) {
                // نحسب كم بلوك من 1000 نقطة عنده
                $blocks = floor($user->points / 1000);

                // النقاط اللي رح تتحول
                $pointsToRedeem = $blocks * 1000;

                // المبلغ بالدولار
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

                $response = [
                    'points_redeemed' => $pointsToRedeem,
                    'amount_usd' => $totalAmount,
                    'wallet_balance' => $user->fresh()->wallet_balance,
                    'points_balance' => $user->fresh()->points,
                ];
                return BaseController::sendResponse($response, __('messages.points_have_been_transferred_successfully'));
            });
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }
}
