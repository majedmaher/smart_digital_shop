<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\CodeResource;
use App\Http\Resources\Dashboard\ProductResource;
use App\Models\Code;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class CodeService extends Controller
{
    static function index(): JsonResponse
    {
        try {
            $codes = Code::with('product')->latest()->get();
            return BaseController::sendResponse(CodeResource::collection($codes), __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    static function ProductCodes($product_id): JsonResponse
    {
        try {
            $codes = Code::where('product_id', $product_id)->latest()->get();
            return BaseController::sendResponse(CodeResource::collection($codes), __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    public static function getProducts(): JsonResponse
    {
        try {
            $products = Product::select('id', 'title')->latest()->get();
            return BaseController::sendResponse(ProductResource::collection($products), __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }

    static function store($data): JsonResponse
    {
        try {
            $data['user_id'] = auth()->id();
            $data['is_used'] = false;
            $code = Code::create($data);
            return BaseController::sendResponse($code, __('messages.store_successfully', ['item' => 'Code']));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.store_failed', ['item' => 'code']), [], 500);
        }
    }

    static function show($id): JsonResponse
    {
        try {
            $code = Code::find($id);
            if ($code == null) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.code')]), [], 404);
            }
            return BaseController::sendResponse($code, __('messages.store_successfully', ['item' => __('messages.cate$category')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.cate$category')]), [$th->getMessage()], 500);
        }
    }

    static function update(int $id, $data): JsonResponse
    {
        try {
            $data['user_id'] = auth()->id();
            $code = Code::find($id);
            if (!$code) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => 'code']), [], 404);
            }
            $code->product_id = $data['product_id'];
            $code->code = $data['code'];
            if ($data['is_used'] === true) {
                $code->is_used = true;
            }
            if ($data['is_used'] === false) {
                $code->is_used = false;
            }
            $code->update();
            return BaseController::sendResponse($code, __('messages.update_successfully', ['item' => 'code']));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.update_failed', ['item' => 'code']), [$th->getMessage()], 500);
        }
    }

    static function delete($id): JsonResponse
    {
        try {
            $code = Code::find($id);

            if (!$code) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => 'code']), [], 404);
            }
            $code->delete();
            return BaseController::sendResponse($code, __('messages.delete_successfully', ['item' => 'code']));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.delete_failed', ['item' => 'code']), [], 500);
        }
    }
}
