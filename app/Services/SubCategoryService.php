<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubCategoryResource;
use App\Models\SubCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;

class SubCategoryService extends Controller
{
    private static string $image_folder = 'subCategories';
    static function index(): JsonResponse
    {
        // $sub_categories = SubCategory::withCount('children')->where(function ($query) {
        //     $query->whereNull('parent_id')->whereHas('children');
        // })->orWhere(function ($query) {
        //     $query->whereNull('parent_id')->whereDoesntHave('children');
        // })->latest()->get();

        $sub_categories = SubCategory::withCount('children')->whereNull('parent_id')->latest()->get();


        // return BaseController::sendResponse($sub_categories, __('messages.sent_data'));
        return BaseController::sendResponse(SubCategoryResource::collection($sub_categories), __('messages.sent_data'));
    }

    static function store($data): JsonResponse
    {
        DB::beginTransaction();
        try {
            if (isset($data['parent_id']) && SubCategory::find($data['parent_id'])->parent_id != null) {
                return BaseController::sendError(__('messages.error_subcategory_parent_id'), [], 403);
            }

            $data['user_id'] = auth()->id();
            $data['icon'] = saveImage($data['icon'], self::$image_folder . '/icons');
            $data['image'] = saveImage($data['image'], self::$image_folder . '/images');
            $sub_category = SubCategory::create($data);
            DB::commit();
            return BaseController::sendResponse(SubCategoryResource::make($sub_category), __('messages.store_successfully', ['item' => __('messages.sub_category')]));
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.sub_category')]), [$th->getMessage()], 500);
        }
    }

    static function update(int $id, $data): JsonResponse
    {
        DB::beginTransaction();
        try {
            // if (SubCategory::find($data['parent_id'])->parent_id != null) {
            //     return BaseController::sendError(__('messages.error_subcategory_parent_id'), [], 403);
            // }
            $data['user_id'] = auth()->id();
            $sub_category = SubCategory::find($id);
            if ($sub_category == null) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.sub_category')]), [], 404);
            }
            if ($data['image'] || $data->hasFile('image')) {
                $sub_category->image = saveImage($data['image'], self::$image_folder . '/image');
            }
            if ($data['icon'] || $data->hasFile('icon')) {
                $sub_category->icon = saveImage($data['icon'], self::$image_folder . '/icons');
            }
            $sub_category->name = $data['name'];
            $sub_category->category_id = $data['category_id'];
            $sub_category->parent_id = $data['parent_id'];
            $sub_category->update();
            DB::commit();
            return BaseController::sendResponse(SubCategoryResource::make($sub_category), __('messages.update_successfully', ['item' => __('messages.sub_category')]));
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseController::sendError(__('messages.update_failed', ['item' => __('messages.sub_category')]), [$th->getMessage()], 500);
        }
    }

    static function delete(int $id)
    {
        DB::beginTransaction();
        try {
            $sub_category = SubCategory::find($id);

            if (!$sub_category || $sub_category == null) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.sub_category')]), [], 404);
            }
            $sub_category->delete();
            DB::commit();
            return BaseController::sendResponse(SubCategoryResource::make($sub_category), __('messages.delete_successfully', ['item' => __('messages.sub_category')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.delete_failed', ['item' => __('messages.sub_category')]), [], 500);
        }
    }
}
