<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\InterestRequest;
use App\Http\Resources\Dashboard\ProductResource;
use App\Models\Interest;
use Illuminate\Http\JsonResponse;

class InterestController extends Controller
{
    public function myInterests(): JsonResponse
    {
        try {
            $products = Interest::where('user_id', auth()->id())
                ->with(['product' => function ($query) {
                    $query->select('id', 'title', 'slug', 'price', 'price_before', 'discount', 'image');  // تحديد الأعمدة التي تريد استرجاعها
                }])
                ->latest()
                ->get()
                ->pluck('product')->flatten();

            return BaseController::sendResponse(ProductResource::collection($products), __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [$th->getMessage()], 500);
        }
    }

    public function store(InterestRequest $request): JsonResponse
    {
        try {
            $interest = Interest::where(['user_id' => auth()->id(), 'product_id' => $request->product_id])->first();
            if ($interest && isset($interest)) {
                return BaseController::sendResponse([], __('messages.this_product_was_previously_stored_for_this_user'));
            }
            $interest = Interest::create(['user_id' => auth()->id(), 'product_id' => $request->product_id]);
            return BaseController::sendResponse($interest, __('messages.store_successfully', ['item' => __('messages.interest')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.interest')]), [], 500);
        }
    }

    public function delete($id): JsonResponse
    {
        try {
            $interest = Interest::find($id);
            if (!$interest || !isset($interest)) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.interest')]), [], 422);
            }
            $interest->delete();
            return BaseController::sendResponse($interest, __('messages.delete_successfully', ['item' => __('messages.interest')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.delete_failed', ['item' => __('messages.interest')]), [], 500);
        }
    }
}
