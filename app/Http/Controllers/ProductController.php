<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function subcategoryProducts($sub_category_id): JsonResponse
    {
        return ProductService::subcategoryProducts($sub_category_id);
    }

    public function index(): JsonResponse
    {
        return ProductService::index();
    }

    public function store(ProductRequest $request): JsonResponse
    {
        return ProductService::store($request->validated());
    }

    public function update($id, Request $request): JsonResponse
    {
        return ProductService::update($id, $request);
    }

    public function delete($id): JsonResponse
    {
        return ProductService::delete($id);
    }
}
