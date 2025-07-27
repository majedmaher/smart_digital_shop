<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SliderResource;
use App\Http\Resources\SubCategoryResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\Slider;
use App\Models\SubCategory;
use Illuminate\Http\JsonResponse;

use function PHPUnit\Framework\isEmpty;

class MainService extends Controller
{
    static function getMobileMainScreen(): JsonResponse
    {
        try {

            $categories = Category::with(['subCategories' => function ($query) {
                $query->withCount('children')->whereNull('parent_id')->latest()->get();
            }])->getNecessaryData()
                ->latest()
                ->get();

            $sliders = Slider::latest()->get();
            $products = Product::where('is_active', 1)->latest()->take(4)->get();


            $data = ['categories' => CategoryResource::collection($categories), 'sliders' => SliderResource::collection($sliders), 'best_seller' => ProductResource::collection($products), 'newly_arrived' => ProductResource::collection($products), 'suggested_products' => ProductResource::collection($products)];

            return BaseController::sendResponse($data, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    static function getCategoriesWithSubCategories(): JsonResponse
    {
        try {

            $categories = Category::with(['subCategories' => function ($query) {
                $query->withCount('children')->whereNull('parent_id')->latest()->get();
            }])->getNecessaryData()
                ->latest()
                ->get();

            return BaseController::sendResponse(CategoryResource::collection($categories), __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    static function getMainContent(): JsonResponse
    {
        try {

            $sliders = Slider::latest()->get();
            $products = Product::where('is_active', 1)->getNecessaryData()->latest()->take(4)->get();


            $data = ['sliders' => SliderResource::collection($sliders), 'best_seller' => ProductResource::collection($products), 'newly_arrived' => ProductResource::collection($products), 'suggested_products' => ProductResource::collection($products)];

            return BaseController::sendResponse($data, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    public static function getSubCategory($slug): JsonResponse
    {
        try {
            // $locale = app()->getLocale(); // 'en' أو 'ar'

            // $subCategory = SubCategory::where("slug->{$locale}", $slug)->first();
            $subCategory = SubCategory::query()->whereJsonContainsLocales('slug', ['en', 'ar'], $slug)->first();
            if ($subCategory === null) return BaseController::sendError(__('messages.search_item_not_found'), [], 422);

            if ($subCategory->children()->exists()) {
                $children = $subCategory->children()->getNecessaryData()->get();
                $response = [
                    'type' => 'children',
                    'children' => SubCategoryResource::collection($children)
                ];
            } else {
                $products = $subCategory->products()->get();
                $response = [
                    'type' => 'products',
                    'products' => ProductResource::collection($products)
                ];
            }

            return BaseController::sendResponse($response, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }

    public static function getProduct($slug): JsonResponse
    {
        try {
            $products = Product::query()->whereJsonContainsLocales('slug', ['en', 'ar'], $slug)->first();
            if ($products === null) return BaseController::sendError(__('messages.search_item_not_found'), [], 422);
            return BaseController::sendResponse(ProductResource::make($products), __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('something wrong'), [], 500);
        }
    }
}
