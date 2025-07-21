<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function store(OrderRequest $request): JsonResponse
    {
        return OrderService::store($request->validated());
    }
}
