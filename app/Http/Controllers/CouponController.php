<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\CouponRequest;
use App\Http\Requests\OrderRequest;
use App\Http\Resources\CouponOrderResponseResource;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;

class CouponController extends Controller
{
    public function create(CouponRequest $request): JsonResponse
    {
        return CouponService::store($request->validated());
    }
    public function applyCoupon(OrderRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $couponResult = CouponService::validateCoupon($data['coupon_code'], auth()->user(), $data['cart']);
            // if (is_array($coupon) && !isset($coupon['coupon'])) return BaseController::sendError($coupon[0], [], $coupon[1]);
            if (isset($couponResult[0]) && is_string($couponResult[0])) {
                return BaseController::sendError($couponResult[0], [], $couponResult[1]);
            }
            $result = CouponService::distributeDiscount($couponResult);

            return BaseController::sendResponse(CouponOrderResponseResource::collection($result), __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [$th->getMessage()], 500);
        }
    }
}
