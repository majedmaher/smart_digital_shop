<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\RoleEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class AuthService extends Controller
{
    // Register
    static function register($data)
    {
        DB::beginTransaction();
        try {
            $user = User::create($data);
            $user->assignRole(RoleEnum::ADMIN); // الافتراضي
            DB::commit();
            return BaseController::sendResponse($user, __('messages.register_successfully'));
        } catch (Throwable $e) {
            DB::rollBack();
            return BaseController::sendError(__('messages.register_failed'), [$e->getMessage()], 500);
        }
    }

    // Login (Issue Token)
    static function login($data)
    {

        try {
            DB::beginTransaction();
            $user = User::where('email', $data->email)->first();

            if (!$user || !Hash::check($data->password, $user->password)) {
                return BaseController::sendError((__('messages.login_failed')), [], 403);
            }
            DB::commit();
            $token = $user->createToken('api-token')->plainTextToken;
            $response = ['token' => $token];

            return BaseController::sendResponse($response, __('messages.login_successfully'));
        } catch (\Throwable $th) {
            Db::rollBack();
            return BaseController::sendError((__('messages.login_failed')), [$th->getMessage()], 500);
        }
    }
}
