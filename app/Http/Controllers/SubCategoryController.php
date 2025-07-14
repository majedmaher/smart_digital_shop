<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubCategoryRequest;
use App\Services\SubCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubCategoryController extends Controller
{

    public function index(): JsonResponse
    {
        return SubCategoryService::index();
    }

    public function store(SubCategoryRequest $request): JsonResponse
    {
        return SubCategoryService::store($request->validated());
    }

    public function update(int $id, Request $request): JsonResponse
    {
        return SubCategoryService::update($id, $request);
    }

    public function delete(int $id): JsonResponse
    {
        return SubCategoryService::delete($id);
    }
}
