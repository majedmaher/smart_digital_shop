<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Mail\OtpCodeMail;
use App\Models\Referral;
use App\Models\User;
use App\RoleEnum;
use Illuminate\Support\Arr;
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
            'date' => 'nullable|date|before:' . now()->subYears(2)->toDateString() . '|after:' . now()->subYears(90)->toDateString(),
            'gender' => 'nullable|string|in:male,female',
        ]);

        try {
            $user = auth()->user();
            // return response()->json([Hash::check($request->password, $user->password)]);
            $user->name = $validated['name'];
            $user->date = $validated['date'];
            $user->gender = $validated['gender'];
            if ($validated['password']) {
                $user->password = $validated['password'];
            }
            $user->update();

            return BaseController::sendResponse($user, __('messages.update_successfully', ['item' => __('messages.user')]));
        } catch (\Throwable $th) {
            return BaseController::sendError((__('messages.login_failed')), [$th->getMessage()], 500);
        }
    }
    static function confirmOtp($data)
    {
        $user = User::where('email', $data['email'])->first();

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
            'user' => $user->only(['id', 'name', 'email'])
        ];
        return BaseController::sendResponse($response, __('messages.login_successfully'));
    }
}
