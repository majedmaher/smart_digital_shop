<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CategoryService extends Controller
{
    static function index(): JsonResponse
    {
        $categories = Category::latest()->get();
        return BaseController::sendResponse($categories, __('messages.sent_data'));
    }

    static function store($data): JsonResponse
    {
        // return response()->json(['data' => $data]);
        DB::beginTransaction();
        try {
            $icon = saveImage($data['icon'], 'categories');
            $category = Category::create([
                "user_id" => auth()->id(),
                "icon" => $icon,
                "name" => $data['name']
            ]);
            DB::commit();
            return BaseController::sendResponse($category, __('messages.store_successfully', ['item' => __('messages.category')]));
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.category')]), [$th->getMessage()], 500);
            //throw $th;
        }
    }
}
