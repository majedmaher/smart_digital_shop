<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getAllUsers(): JsonResponse
    {
        return UserService::getAllUsers();
    }

    public function getPermissions(): JsonResponse
    {
        return UserService::getPermissions();
    }

    public function createCustomerUser(RegisterRequest $request): JsonResponse
    {
        return UserService::createCustomerUser($request->validated());
    }

    public function updateUserPermissions(Request $request, User $user): JsonResponse
    {
        return UserService::updateUserPermissions($request, $user);
    }
}
