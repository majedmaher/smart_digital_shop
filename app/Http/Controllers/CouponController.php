<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\CouponRequest;
use App\Http\Requests\OrderRequest;
use App\Http\Resources\CouponResponseResource;
use App\Models\coupon;
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
        $data = $request->validated();
        $coupon = CouponService::applyCoupon($data['coupon_code'], auth()->user(), $data['cart']);
        if (is_array($coupon)) return BaseController::sendError($coupon[0], [], $coupon[1]);
        return BaseController::sendResponse(CouponResponseResource::make($coupon), __('messages.sent_data'));
    }
}
