<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\CouponRequest;
use App\Models\coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function create(CouponRequest $request): JsonResponse
    {
        try {
            $coupon = coupon::create($request->validated());
            return BaseController::sendResponse($coupon, __('messages.store_successfully', ['item' => __('messages.coupon')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.coupon')]), [], 500);
        }
    }
}
