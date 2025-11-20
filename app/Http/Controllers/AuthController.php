<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Services\AuthService;
use App\Services\OtpService;
use App\Models\User;
use App\Mail\OtpCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends BaseController
{
    // Register
    public function register(RegisterRequest $request)
    {
        return AuthService::register($request->validated());
    }

    // Login (Issue Token)
    public function login(LoginRequest $request)
    {

        return AuthService::login($request);
    }

    // Register with Phone
    public function registerWithPhone(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'referral_code' => 'nullable|string|exists:users,referral_code',
        ]);

        // OTP معطل مؤقتاً - إرجاع token مباشرة
        $result = AuthService::register($data);
        
        // إذا كان التسجيل ناجح، إنشاء token مباشرة
        if ($result->getData()->success ?? false) {
            $user = User::where('phone', $data['phone'])->first();
            if ($user) {
                $token = $user->createToken('auth_token')->plainTextToken;
                return BaseController::sendResponse([
                    'token' => $token,
                    'user' => array_merge(
                        $user->only(['id', 'name', 'email', 'phone']),
                        [
                            'roles' => $user->getRoleNames(),
                            'permissions' => $user->getPermissionNames(),
                            'is_admin' => $user->hasRole(\App\RoleEnum::ADMIN),
                        ]
                    )
                ], __('messages.register_successfully'));
            }
        }
        
        return $result;
    }

    // Login with Phone
    public function loginWithPhone(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|exists:users,phone',
            'password' => 'required|string',
        ]);

        try {
            $user = User::where('phone', $data['phone'])->first();

            if (!$user || !Hash::check($data['password'], $user->password)) {
                return BaseController::sendError((__('messages.login_failed')), [], 403);
            }

            // OTP معطل مؤقتاً - إرجاع token وبيانات المستخدم مباشرة
            // TODO: تفعيل OTP عند اختيار مزود SMS مناسب
            // $otp = OtpService::generate();
            // $user->otp_code = $otp;
            // $user->otp_expires_at = OtpService::expiresAt();
            // $user->save();
            // if ($user->email) {
            //     Mail::to($user->email)->send(new OtpCodeMail($otp));
            // }
            // return BaseController::sendResponse([], __('messages.verification_code_sent'));

            // إرجاع token وبيانات المستخدم مباشرة
            $token = $user->createToken('auth_token')->plainTextToken;
            $response = [
                'token' => $token,
                'user' => array_merge(
                    $user->only(['id', 'name', 'email', 'phone']),
                    [
                        'roles' => $user->getRoleNames(),
                        'permissions' => $user->getPermissionNames(),
                        'is_admin' => $user->hasRole(\App\RoleEnum::ADMIN),
                    ]
                )
            ];
            
            return BaseController::sendResponse($response, __('messages.login_successfully'));
        } catch (\Throwable $th) {
            return BaseController::sendError((__('messages.login_failed')), [$th->getMessage()], 500);
        }
    }

    public function updateUser(Request $request)
    {

        return AuthService::updateUser($request);
    }

    public function confirmOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'nullable|email|exists:users,email',
            'phone' => 'nullable|string|exists:users,phone',
            'otp'   => 'required|string|size:6',
        ]);

        // Ensure either email or phone is provided
        if (!$data['email'] && !$data['phone']) {
            return BaseController::sendError(__('messages.validation_error'), ['email or phone is required'], 422);
        }

        return AuthService::confirmOtp($data);
    }

    // Logout (Revoke Token)
    public function logout(Request $request)
    {
        // return response()->json(['data' => $request]);
        $request->user()->currentAccessToken()->delete();

        return $this->sendResponse(null, __('messages.Logged_out_successfully'));
    }

    /**
     * Send password reset link to user's email
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        return AuthService::forgotPassword($request->email);
    }

    /**
     * Reset user password using token
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        return AuthService::resetPassword(
            $request->email,
            $request->token,
            $request->password
        );
    }
}
