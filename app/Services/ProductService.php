<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\ProductResource as DashboardProductResource;
use App\Http\Resources\Dashboard\SubCategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProductService extends Controller
{
    private static string $image_folder = 'products';
    static function index(): JsonResponse
    {
        $products = Product::getNecessaryData()->withCount('codes')->latest()->get();

        return BaseController::sendResponse(DashboardProductResource::collection($products), __('messages.sent_data'));
    }
    static function subcategoryProducts($sub_category_id): JsonResponse
    {
        $products = Product::where('sub_category_id', $sub_category_id)->withCount('codes')->getNecessaryData()->latest()->get();

        return BaseController::sendResponse($products, __('messages.sent_data'));
    }

    static function categorySubcategoryProducts($category_id): JsonResponse
    {
        try {
            $leafSubCategories = SubCategory::where('category_id', $category_id)
                ->whereDoesntHave('children')
                ->select('id', 'name')->get();


            return BaseController::sendResponse(SubCategoryResource::collection($leafSubCategories), __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError($th->getMessage(), [], 500);
        }
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

    static function show($id): JsonResponse
    {
        try {
            $product = Product::find($id);
            if ($product == null) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.product')]), [], 404);
            }
            return BaseController::sendResponse($product, __('messages.store_successfully', ['item' => __('messages.product')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.product')]), [$th->getMessage()], 500);
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
