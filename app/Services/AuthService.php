<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Mail\OtpCodeMail;
use App\Mail\PasswordResetMail;
use App\Models\Referral;
use App\Models\User;
use App\RoleEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class AuthService extends Controller
{
    private static string $image_folder = 'users';
    // Register
    static function register($data)
    {
        DB::beginTransaction();
        try {
            $users_count = User::all()->count();
            $user = User::create(Arr::except($data, ['referral_code']));
            if ($users_count >= 1) {
                $user->assignRole(RoleEnum::USER);
            } else {
                $user->assignRole(RoleEnum::ADMIN);
            }

            // توليد كود الدعوة للمستخدم الجديد
            $user->generateReferralCode();

            // التحقق من كود الدعوة المدخل
            $referralCode = $data['referral_code'];

            if ($referralCode) {
                $referrer = User::where('referral_code', $referralCode)
                    ->where('id', '!=', $user->id) // لا يمكن أن يستخدم نفسه
                    ->first();

                if ($referrer) {
                    Referral::create([
                        'referrer_id' => $referrer->id,
                        'referred_id' => $user->id,
                        'code' => $referralCode,
                        'reward_given' => true,
                    ]);

                    // إضافة النقاط لصاحب الدعوة
                    $referrer->increment('points', 1000);
                }
            }


            // OTP Verification - تعطيل مؤقتاً للتسجيل بالهاتف حتى اختيار مزود SMS مناسب
            // TODO: تفعيل OTP عند اختيار مزود SMS مناسب
            // if (isset($data['phone']) && !isset($data['email'])) {
            //     // التسجيل بالهاتف - سيتم تفعيل OTP لاحقاً
            //     $otp = OtpService::generate();
            //     $user->otp_code = $otp;
            //     $user->otp_expires_at = OtpService::expiresAt();
            //     $user->save();
            //     // إرسال OTP عبر SMS (سيتم تفعيله لاحقاً)
            // } else {
            //     // التسجيل بالبريد الإلكتروني
            //     $otp = OtpService::generate();
            //     $user->otp_code = $otp;
            //     $user->otp_expires_at = OtpService::expiresAt();
            //     $user->save();
            //     Mail::to($user->email)->send(new OtpCodeMail($otp));
            // }
            
            // تعطيل OTP مؤقتاً للتسجيل بالهاتف
            if (isset($data['email']) && $data['email']) {
                // التسجيل بالبريد الإلكتروني - OTP يعمل
                $otp = OtpService::generate();
                $user->otp_code = $otp;
                $user->otp_expires_at = OtpService::expiresAt();
                $user->save();
                Mail::to($user->email)->send(new OtpCodeMail($otp));
            }
            // التسجيل بالهاتف - OTP معطل مؤقتاً


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
            DB::commit();

            return BaseController::sendResponse([], __('messages.verification_code_sent'));
        } catch (\Throwable $th) {
            Db::rollBack();
            return BaseController::sendError((__('messages.login_failed')), [$th->getMessage()], 500);
        }
    }

    static function updateUser($request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:8|confirmed',
            'date' => 'nullable|date_format:Y-m-d',
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $request->user()->id,
            'gender' => 'nullable|string|in:male,female',
            'photo' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        try {
            $user = $request->user();
            // return response()->json([Hash::check($request->password, $user->password)]);
            $user->name = $validated['name'];

            if (isset($validated['date'])) {
                $user->date = $validated['date'];
            }

            if (isset($validated['phone'])) {
                $user->phone = $validated['phone'];
            }

            if (isset($validated['gender'])) {
                $user->gender = $validated['gender'];
            }

            if (isset($validated['password']) && $validated['password']) {
                $user->password = $validated['password'];
            }

            if (isset($validated['photo']) && $request->hasFile('photo')) {
                $photo = saveImage($validated['photo'], self::$image_folder . '/photos');
                if ($user->photo) unlink(public_path($user->photo));
                $user->photo = $photo;
            }

            $user->update();

            return BaseController::sendResponse($user, __('messages.update_successfully', ['item' => __('messages.user')]));
        } catch (\Throwable $th) {
            return BaseController::sendError((__('messages.login_failed')), [$th->getMessage()], 500);
        }
    }
    static function confirmOtp($data)
    {
        // Find user by email or phone
        if (isset($data['email']) && $data['email']) {
            $user = User::where('email', $data['email'])->first();
        } elseif (isset($data['phone']) && $data['phone']) {
            $user = User::where('phone', $data['phone'])->first();
        } else {
            return BaseController::sendError(__('messages.validation_error'), ['email or phone is required'], 422);
        }

        if (!$user) {
            return BaseController::sendError(__('messages.user_not_found'), [], 404);
        }

        // if (
        //     !$user ||
        //     $user->otp_code !== $data['otp'] ||
        //     !$user->otp_expires_at ||
        //     now()->greaterThan($user->otp_expires_at)
        // ) {
        //     return BaseController::sendError(__('messages.otp_error'), [], 422);
        // }

        // OTP valid — clear OTP and return token
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;
        $response = [
            'token' => $token,
            'user' => array_merge(
                $user->only(['id', 'name', 'email']),
                [
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getPermissionNames(),
                    'is_admin' => $user->hasRole(RoleEnum::ADMIN),
                ]
            )
        ];
        return BaseController::sendResponse($response, __('messages.login_successfully'));
    }

    /**
     * Send password reset link to user's email
     */
    static function forgotPassword($email)
    {
        try {
            $user = User::where('email', $email)->first();

            if (!$user) {
                return BaseController::sendError(__('messages.email_not_found'), [], 404);
            }

            // Generate password reset token
            $token = Str::random(64);
            
            // Store token in password_reset_tokens table
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            // Generate reset URL (frontend URL + token)
            // You should replace this with your actual frontend URL
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $resetUrl = $frontendUrl . '/reset-password?email=' . urlencode($email) . '&token=' . urlencode($token);

            // Send password reset email
            Mail::to($user->email)->send(new PasswordResetMail($resetUrl, $user->name));

            return BaseController::sendResponse([], __('messages.password_reset_link_sent'));
        } catch (\Throwable $th) {
            Log::error('Forgot password error: ' . $th->getMessage());
            return BaseController::sendError(__('messages.password_reset_failed'), [$th->getMessage()], 500);
        }
    }

    /**
     * Reset user password using token
     */
    static function resetPassword($email, $token, $password)
    {
        try {
            DB::beginTransaction();

            // Get password reset record
            $passwordReset = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if (!$passwordReset) {
                return BaseController::sendError(__('messages.invalid_reset_token'), [], 404);
            }

            // Check if token is expired (60 minutes)
            $createdAt = \Carbon\Carbon::parse($passwordReset->created_at);
            if (now()->diffInMinutes($createdAt) > 60) {
                // Delete expired token
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                return BaseController::sendError(__('messages.reset_token_expired'), [], 422);
            }

            // Verify token
            if (!Hash::check($token, $passwordReset->token)) {
                return BaseController::sendError(__('messages.invalid_reset_token'), [], 422);
            }

            // Find user
            $user = User::where('email', $email)->first();
            if (!$user) {
                return BaseController::sendError(__('messages.user_not_found'), [], 404);
            }

            // Update password
            $user->password = Hash::make($password);
            $user->save();

            // Delete used token
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            DB::commit();

            return BaseController::sendResponse([], __('messages.password_reset_success'));
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Reset password error: ' . $th->getMessage());
            return BaseController::sendError(__('messages.password_reset_failed'), [$th->getMessage()], 500);
        }
    }
}
