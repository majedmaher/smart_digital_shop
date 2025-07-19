<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class MainService extends Controller
{
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
}
