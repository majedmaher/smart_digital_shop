<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubCategoryRequest;
use App\Services\SubCategoryService;
use Illuminate\Http\JsonResponse;

class SubCategoryController extends Controller
{

    public function getCategories(): JsonResponse
    {
        return SubCategoryService::getCategories();
    }

    public function getSubCategoriesByCategory($categoryId): JsonResponse
    {
        return SubCategoryService::getSubCategoriesByCategory($categoryId);
    }

    public function index(): JsonResponse
    {
        return SubCategoryService::index();
    }

    public function store(SubCategoryRequest $request): JsonResponse
    {
        return SubCategoryService::store($request->validated());
    }

    public function show($id): JsonResponse
    {
        return SubCategoryService::show($id);
    }

    public function update(int $id, SubCategoryRequest $request): JsonResponse
    {
        return SubCategoryService::update($id, $request);
    }

    public function delete(int $id): JsonResponse
    {
        return SubCategoryService::delete($id);
    }
}
