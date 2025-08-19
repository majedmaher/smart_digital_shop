<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Models\User;
use App\RoleEnum;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserService
{
    public static function getAllUsers(): JsonResponse
    {
        try {
            $users = User::role('custom')->where('id', '!=', auth()->id())->get();
            return BaseController::sendResponse($users, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }

    public static function getPermissions(): JsonResponse
    {
        try {
            $permissions = Permission::select('name')->get();
            return BaseController::sendResponse($permissions, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }

    public static function createCustomerUser($data): JsonResponse
    {
        try {
            $user = User::create($data);
            $user->assignRole(RoleEnum::CUSTOM);
            return BaseController::sendResponse($user, __('messages.store_successfully', ['item' => __('messages.user')]));
        } catch (\Throwable $e) {
            return BaseController::sendError(__('messages.register_failed'), [$e->getMessage()], 500);
        }
    }

    public static function updateUserPermissions($request, $user): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name|in:manage users,manage settings,manage categories,manage subcategories,manage products,manage codes,manage coupons,manage sliders,manage orders,manage ratings,reply tickets',
        ]);
        try {
            $user->syncPermissions($validated['permissions']);
            return BaseController::sendResponse($user, __('messages.update_successfully', ['item' => __('messages.user')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [$th->getMessage()], 500);
        }
    }
}
