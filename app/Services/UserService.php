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
            $users = User::where('id', '!=', auth()->id())->get()->map(function($user) {
                return array_merge(
                    $user->only(['id', 'name', 'email', 'phone', 'photo', 'date', 'gender', 'points', 'wallet_balance', 'referral_code', 'created_at', 'updated_at']),
                    [
                        'roles' => $user->getRoleNames(),
                        'permissions' => $user->getPermissionNames(),
                        'is_admin' => $user->hasRole(RoleEnum::ADMIN),
                    ]
                );
            });
            return BaseController::sendResponse($users, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }

    public static function getAllCustomerUsers(): JsonResponse
    {
        try {
            $users = User::role(RoleEnum::USER)->where('id', '!=', auth()->id())->get()->map(function($user) {
                return array_merge(
                    $user->only(['id', 'name', 'email', 'phone', 'photo', 'date', 'gender', 'points', 'wallet_balance', 'referral_code', 'created_at', 'updated_at']),
                    [
                        'roles' => $user->getRoleNames(),
                        'permissions' => $user->getPermissionNames(),
                        'is_admin' => $user->hasRole(RoleEnum::ADMIN),
                    ]
                );
            });
            return BaseController::sendResponse($users, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }

    public static function getAllEmployees(): JsonResponse
    {
        try {
            $employees = User::whereHas('roles', function($query) {
                $query->whereIn('name', [RoleEnum::ADMIN->value, RoleEnum::MODERATOR->value, RoleEnum::CUSTOM->value]);
            })->where('id', '!=', auth()->id())->get()->map(function($user) {
                return array_merge(
                    $user->only(['id', 'name', 'email', 'phone', 'photo', 'date', 'gender', 'points', 'wallet_balance', 'referral_code', 'created_at', 'updated_at']),
                    [
                        'roles' => $user->getRoleNames(),
                        'permissions' => $user->getPermissionNames(),
                        'is_admin' => $user->hasRole(RoleEnum::ADMIN),
                    ]
                );
            });
            return BaseController::sendResponse($employees, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }

    public static function deleteUser($user_id): JsonResponse
    {
        try {
            $user = User::find($user_id);
            if (!$user) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.user')]), [], 404);
            }
            $user->delete();
            return BaseController::sendResponse($user, __('messages.delete_successfully', ['item' => __('messages.user')]));
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
            // Extract permissions from data
            $permissions = $data['permissions'] ?? null;
            unset($data['permissions']);

            $user = User::create($data);
            $user->assignRole(RoleEnum::USER);

            // إذا تم إرسال صلاحيات محددة، قم بتعيينها للعميل
            if ($permissions && is_array($permissions)) {
                $user->syncPermissions($permissions);
            }

            // Format user data with roles and permissions
            $userData = array_merge(
                $user->only(['id', 'name', 'email', 'phone', 'photo', 'date', 'gender', 'points', 'wallet_balance', 'referral_code', 'created_at', 'updated_at']),
                [
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getPermissionNames(),
                    'is_admin' => $user->hasRole(RoleEnum::ADMIN),
                ]
            );

            return BaseController::sendResponse($userData, __('messages.store_successfully', ['item' => __('messages.user')]));
        } catch (\Throwable $e) {
            return BaseController::sendError(__('messages.register_failed'), [$e->getMessage()], 500);
        }
    }

    public static function createEmployee($data): JsonResponse
    {
        try {
            // Extract permissions from data
            $permissions = $data['permissions'] ?? null;
            unset($data['permissions']);

            $user = User::create($data);
            $user->assignRole(RoleEnum::CUSTOM);

            // إذا تم إرسال صلاحيات محددة، قم بتعيينها للموظف
            if ($permissions && is_array($permissions)) {
                $user->syncPermissions($permissions);
            }

            // Format user data with roles and permissions
            $userData = array_merge(
                $user->only(['id', 'name', 'email', 'phone', 'photo', 'date', 'gender', 'points', 'wallet_balance', 'referral_code', 'created_at', 'updated_at']),
                [
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getPermissionNames(),
                    'is_admin' => $user->hasRole(RoleEnum::ADMIN),
                ]
            );

            return BaseController::sendResponse($userData, __('messages.store_successfully', ['item' => __('messages.user')]));
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

            // Refresh user to get updated permissions
            $user->refresh();

            // Format user data with roles and permissions
            $userData = array_merge(
                $user->only(['id', 'name', 'email', 'phone', 'photo', 'date', 'gender', 'points', 'wallet_balance', 'referral_code', 'created_at', 'updated_at']),
                [
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getPermissionNames(),
                    'is_admin' => $user->hasRole(RoleEnum::ADMIN),
                ]
            );

            return BaseController::sendResponse($userData, __('messages.update_successfully', ['item' => __('messages.user')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [$th->getMessage()], 500);
        }
    }
}
