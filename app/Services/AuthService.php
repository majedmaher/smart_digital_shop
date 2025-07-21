<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Mail\OtpCodeMail;
use App\Models\User;
use App\RoleEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AuthService extends Controller
{
    // Register
    static function register($data)
    {
        DB::beginTransaction();
        try {
            $user = User::create($data);
            if ($user->id === 1) {
                $user->assignRole(RoleEnum::ADMIN);
            } else {
                $user->assignRole(RoleEnum::USER);
            }
            $otp = OtpService::generate();
            $user->otp_code = $otp;
            $user->otp_expires_at = OtpService::expiresAt();
            $user->save();

            Mail::to($user->email)->send(new OtpCodeMail($otp));


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

            // Generate and send OTP
            $otp = OtpService::generate();
            $user->otp_code = $otp;
            $user->otp_expires_at = OtpService::expiresAt();
            $user->save();

            Mail::to($data->email)->send(new OtpCodeMail($otp));
            // Mail::to($user->email)->send(new OtpCodeMail($otp));
            DB::commit();
            // $token = $user->createToken('api-token')->plainTextToken;
            // $response = ['token' => $token];

            return BaseController::sendResponse([], __('messages.verification_code_sent'));
        } catch (\Throwable $th) {
            Db::rollBack();
            return BaseController::sendError((__('messages.login_failed')), [$th->getMessage()], 500);
        }
    }
    static function confirmOtp($data)
    {
        $user = User::where('email', $data['email'])->first();

        if (
            !$user ||
            $user->otp_code !== $data['otp'] ||
            !$user->otp_expires_at ||
            now()->greaterThan($user->otp_expires_at)
        ) {
            return BaseController::sendError(__('messages.otp_error'), [], 422);
        }

        // OTP valid — clear OTP and return token
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;
        $response = ['token' => $token, 'user' => $user];

        return BaseController::sendResponse($response, __('messages.login_successfully'));
    }
}
