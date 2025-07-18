<?php

namespace App\Http\Controllers;

use App\Http\Requests\CodeRequest;
use App\Services\CodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CodeController extends Controller
{
    public function index($sub_category_id): JsonResponse
    {
        return CodeService::index($sub_category_id);
    }

    public function store(CodeRequest $request): JsonResponse
    {
        return CodeService::store($request->validated());
    }

    public function update($id, CodeRequest $request): JsonResponse
    {
        return CodeService::update($id, $request);
    }

    public function delete($id): JsonResponse
    {
        return CodeService::delete($id);
    }
}
