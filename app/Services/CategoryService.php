<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CategoryService extends Controller
{
    static function index(): JsonResponse
    {
        $categories = Category::getNecessaryData()
            ->latest()
            ->get();

        return BaseController::sendResponse(CategoryResource::collection($categories), __('messages.sent_data'));
    }

    static function store($data): JsonResponse
    {
        DB::beginTransaction();
        try {
            $icon = saveImage($data['icon'], 'categories');
            $category = Category::create([
                "user_id" => auth()->id(),
                "icon" => $icon,
                "name" => $data['name']
            ]);

            DB::commit();
            return BaseController::sendResponse(CategoryResource::make($category), __('messages.store_successfully', ['item' => __('messages.category')]));
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.category')]), [$th->getMessage()], 500);
        }
    }

    static function update($id, $data): JsonResponse
    {
        DB::beginTransaction();
        try {
            $category = Category::find($id);
            if (!$category || $category == null) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.category')]), [], 404);
            }
            if ($data['icon']) {
                $icon = saveImage($data['icon'], 'categories');
                $category->icon = $icon;
            }
            $category->name = $data['name'];
            $category->update();
            DB::commit();
            return BaseController::sendResponse(CategoryResource::make($category), __('messages.update_successfully', ['item' => __('messages.category')]));
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseController::sendError(__('messages.update_failed', ['item' => __('messages.category')]), [$th->getMessage()], 500);
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
            return BaseController::sendError(__('messages.delete_failed', ['item' => __('messages.category')]), [$th->getMessage()], 500);
        }
    }
}
