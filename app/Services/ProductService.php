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

    static function store($request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'image' => 'required|file|mimes:png,jpg,jpeg,webp|max:2048',
            ]);
            $data = $request->validated();
            $data['user_id'] = auth()->id() ?? 1; // Default to admin user if not authenticated
            $data['image'] = saveImage($data['image'], self::$image_folder);
            
            // Ensure subcategory is set
            if (empty($data['sub_category_id'])) {
                $data['sub_category_id'] = Product::getDefaultSubCategoryId();
            }
            
            $product = Product::create($data);
            DB::commit();
            return BaseController::sendResponse($product, __('messages.store_successfully', ['item' => __('messages.product')]));
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.product')]), [$th->getMessage()], 500);
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
        try {
            $data['user_id'] = auth()->id() ?? 1; // Default to admin user if not authenticated
            $product = Product::find($id);
            // return BaseController::sendResponse($product, __('messages.update_successfully', ['item' => __('messages.product')]));
            if (!$product) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.product')]), [], 404);
            }
            $product->fill($data->only(['title', 'category_id', 'sub_category_id', 'content', 'description', 'terms_and_conditions', 'price_before', 'price', 'discount', 'vat_rate', 'shipping_payment', 'status']));

            if ($data['image'] || $data->hasFile('image')) {
                if ($product->image) unlink(public_path($product->image));
                $product->image = saveImage($data['image'], self::$image_folder);
            }
            $product->update();
            return BaseController::sendResponse($product, __('messages.update_successfully', ['item' => __('messages.product')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.product')]), [$th->getMessage()], 500);
        }
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

    /**
     * Ensure all products have a subcategory
     */
    static function ensureAllProductsHaveSubCategory(): JsonResponse
    {
        try {
            $productsWithoutSubCategory = Product::withoutSubCategory()->get();
            $defaultSubCategoryId = Product::getDefaultSubCategoryId();
            
            $updatedCount = 0;
            foreach ($productsWithoutSubCategory as $product) {
                $product->sub_category_id = $defaultSubCategoryId;
                $product->save();
                $updatedCount++;
            }

            return BaseController::sendResponse([
                'updated_count' => $updatedCount,
                'default_subcategory_id' => $defaultSubCategoryId,
                'message' => "تم تحديث {$updatedCount} منتج لاستخدام الفئة الفرعية الافتراضية"
            ], __('messages.success'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.error_occurred'), [$th->getMessage()], 500);
        }
    }

    /**
     * Get products statistics including subcategory distribution
     */
    static function getProductsStats(): JsonResponse
    {
        try {
            $totalProducts = Product::count();
            $productsWithSubCategory = Product::whereNotNull('sub_category_id')->count();
            $productsWithoutSubCategory = Product::whereNull('sub_category_id')->count();
            $productsWithDefaultSubCategory = Product::withDefaultSubCategory()->count();

            $subcategoryDistribution = Product::select('sub_category_id')
                ->with('subCategory:id,name')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('sub_category_id')
                ->get();

            return BaseController::sendResponse([
                'total_products' => $totalProducts,
                'products_with_subcategory' => $productsWithSubCategory,
                'products_without_subcategory' => $productsWithoutSubCategory,
                'products_with_default_subcategory' => $productsWithDefaultSubCategory,
                'subcategory_distribution' => $subcategoryDistribution,
            ], __('messages.success'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.error_occurred'), [$th->getMessage()], 500);
        }
    }
}
