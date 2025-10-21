<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function getAllEmployees(): JsonResponse
    {
        return UserService::getAllEmployees();
    }

    public function createEmployee(RegisterRequest $request): JsonResponse
    {
        return UserService::createEmployee($request->validated());
    }

    public function deleteEmployee($user_id): JsonResponse
    {
        return UserService::deleteUser($user_id);
    }
}

