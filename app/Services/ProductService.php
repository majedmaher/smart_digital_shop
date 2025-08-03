<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;

class ProductService extends Controller
{
    private static string $image_folder = 'products';
    static function index($sub_category_id): JsonResponse
    {
        $products = Product::where('sub_category_id', $sub_category_id)->latest()->get();

        return BaseController::sendResponse(ProductResource::collection($products), __('messages.sent_data'));
    }

    static function store($data): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data['user_id'] = auth()->id();
            $data['image'] = saveImage($data['image'], self::$image_folder);
            $product = Product::create($data);
            DB::commit();
            return BaseController::sendResponse($product, __('messages.store_successfully', ['item' => __('messages.product')]));
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.product')]), 500);
        }
    }

    static function update($id, $data): JsonResponse
    {
        $data['user_id'] = auth()->id();
        $product = Product::find($id);
        // return BaseController::sendResponse($product, __('messages.update_successfully', ['item' => __('messages.product')]));
        if (!$product) {
            return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.product')]), [], 404);
        }
        $product->fill($data->only(['name', 'category_id', 'sub_category_id', 'content', 'description', 'price_before', 'price', 'discount', 'shipping_payment', 'status']));

        if ($data['image'] || $data->hasFile('image')) {
            $product->image = saveImage($data['image'], self::$image_folder);
        }
        $product->update();
        return BaseController::sendResponse($product, __('messages.update_successfully', ['item' => __('messages.product')]));
    }

    static function delete($id): JsonResponse
    {
        $product = Product::find($id);
        if (!$product) {
            return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.product')]), [], 404);
        }
        $product->delete();

        return BaseController::sendResponse($product, __('messages.delete_successfully', ['item' => __('messages.product')]));
    }
}
