<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return CategoryService::index();
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        return CategoryService::store($request->validated());
    }
    public function update($id, Request $request): JsonResponse
    {
        return CategoryService::update($id, $request);
    }
    public function delete($id): JsonResponse
    {
        return CategoryService::delete($id);
    }
}
