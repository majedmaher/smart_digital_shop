<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\Dashboard\CategoryResource as DashboardCategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CategoryService extends Controller
{
    private static string $image_folder = 'categories';
    static function index(): JsonResponse
    {
        $categories = Category::getNecessaryData()->withCount('subCategories')->withCount('products')
            ->latest()
            ->get();

        return BaseController::sendResponse(DashboardCategoryResource::collection($categories), __('messages.sent_data'));
    }

    static function store($data): JsonResponse
    {
        DB::beginTransaction();
        try {
            // $icon = saveImage($data['icon'], self::$image_folder . '/icons');
            // $image = saveImage($data['image'], self::$image_folder . '/images');
            $category = Category::create([
                "user_id" => auth()->id(),
                "icon" => saveImage($data['icon'], self::$image_folder . '/icons'),
                "image" => saveImage($data['image'], self::$image_folder . '/images'),
                "name" => $data['name'],
            ]);

            DB::commit();
            return BaseController::sendResponse(CategoryResource::make($category), __('messages.store_successfully', ['item' => __('messages.category')]));
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.category')]), [$th->getMessage()], 500);
        }
    }

    static function show($id): JsonResponse
    {
        try {
            $category = Category::find($id);
            if ($category == null) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.category')]), [], 404);
            }
            return BaseController::sendResponse($category, __('messages.store_successfully', ['item' => __('messages.category')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.category')]), [$th->getMessage()], 500);
        }
    }

    static function update($id, $data): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data['user_id'] = auth()->id();
            $category = Category::find($id);
            if (!$category || $category == null) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.category')]), [], 404);
            }
            if ($data['icon'] || $data->hasFile('icon')) {
                $icon = saveImage($data['icon'], self::$image_folder) . '/icons';
                if ($category->icon) unlink(public_path($category->icon));
                $category->icon = $icon;
            }
            if ($data['image'] || $data->hasFile('image')) {
                $image = saveImage($data['image'], self::$image_folder . '/image');
                if ($category->image) unlink(public_path($category->image));
                $category->image = $image;
            }
            $category->name = $data['name'];
            $category->update();
            DB::commit();
            return BaseController::sendResponse(CategoryResource::make($category), __('messages.update_successfully', ['item' => __('messages.category')]));
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseController::sendError(__('messages.update_failed', ['item' => __('messages.category')]), [], 500);
        }
    }

    static function delete($id)
    {
        DB::beginTransaction();
        try {
            $category = Category::find($id);

            if (!$category || $category == null) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.category'), [], 404]));
            }
            $category->delete();
            DB::commit();
            return BaseController::sendResponse(CategoryResource::make($category), __('messages.delete_successfully', ['item' => __('messages.category')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.delete_failed', ['item' => __('messages.category')]), [], 500);
        }
    }
}
