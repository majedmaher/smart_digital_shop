<?php

namespace App\Services;

use App\Http\Controllers\API\BaseController;
use App\Models\SuspiciousTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SuspiciousTransactionService
{
    private const RISK_THRESHOLD = 70; // عتبة المخاطر
    private const COUNTRY_MISMATCH_PENALTY = 50; // عقوبة اختلاف الدولة
    private const HIGH_AMOUNT_PENALTY = 30; // عقوبة المبلغ المرتفع
    private const MULTIPLE_ATTEMPTS_PENALTY = 40; // عقوبة المحاولات المتعددة

    /**
     * Analyze transaction for suspicious activity
     */
    public static function analyzeTransaction(int $paymentId, string $userIp, array $paymentData = []): JsonResponse
    {
        try {
            $payment = Payment::with(['order.user'])->find($paymentId);

            if (!$payment) {
                return BaseController::sendError(__('messages.payment_not_found'), [], 404);
            }

            $user = $payment->order->user;
            $riskScore = 0;
            $riskFactors = [];
            $isSuspicious = false;

            // 1. تحليل اختلاف الدولة
            $countryAnalysis = self::analyzeCountryMismatch($userIp, $paymentData);
            if ($countryAnalysis['is_mismatch']) {
                $riskScore += self::COUNTRY_MISMATCH_PENALTY;
                $riskFactors[] = [
                    'type' => 'country_mismatch',
                    'description' => __('messages.country_mismatch_detected'),
                    'details' => $countryAnalysis,
                    'penalty' => self::COUNTRY_MISMATCH_PENALTY
                ];
            }

            // 2. تحليل المبلغ المرتفع
            $amountAnalysis = self::analyzeHighAmount($payment->amount_cents, $user);
            if ($amountAnalysis['is_high']) {
                $riskScore += self::HIGH_AMOUNT_PENALTY;
                $riskFactors[] = [
                    'type' => 'high_amount',
                    'description' => __('messages.high_amount_detected'),
                    'details' => $amountAnalysis,
                    'penalty' => self::HIGH_AMOUNT_PENALTY
                ];
            }

            // 3. تحليل المحاولات المتعددة
            $attemptsAnalysis = self::analyzeMultipleAttempts($user, $payment->amount_cents);
            if ($attemptsAnalysis['is_multiple']) {
                $riskScore += self::MULTIPLE_ATTEMPTS_PENALTY;
                $riskFactors[] = [
                    'type' => 'multiple_attempts',
                    'description' => __('messages.multiple_attempts_detected'),
                    'details' => $attemptsAnalysis,
                    'penalty' => self::MULTIPLE_ATTEMPTS_PENALTY
                ];
            }

            // 4. تحليل الوقت غير المعتاد
            $timeAnalysis = self::analyzeUnusualTime($user);
            if ($timeAnalysis['is_unusual']) {
                $riskScore += 20;
                $riskFactors[] = [
                    'type' => 'unusual_time',
                    'description' => __('messages.unusual_time_detected'),
                    'details' => $timeAnalysis,
                    'penalty' => 20
                ];
            }

            // تحديد ما إذا كانت المعاملة مشبوهة
            $isSuspicious = $riskScore >= self::RISK_THRESHOLD;

            // حفظ تحليل المعاملة المشبوهة
            if ($isSuspicious) {
                $suspiciousTransaction = SuspiciousTransaction::create([
                    'payment_id' => $paymentId,
                    'user_id' => $user->id,
                    'risk_score' => $riskScore,
                    'risk_factors' => json_encode($riskFactors),
                    'user_ip' => $userIp,
                    'user_country' => $countryAnalysis['user_country'] ?? null,
                    'card_country' => $countryAnalysis['card_country'] ?? null,
                    'amount_cents' => $payment->amount_cents,
                    'status' => 'pending_review',
                    'analyzed_at' => now(),
                ]);

                // إرسال إشعار للمشرفين
                self::notifyAdmins($suspiciousTransaction);

                Log::warning("Suspicious transaction detected", [
                    'payment_id' => $paymentId,
                    'user_id' => $user->id,
                    'risk_score' => $riskScore,
                    'risk_factors' => $riskFactors
                ]);
            }

            return BaseController::sendResponse([
                'payment_id' => $paymentId,
                'is_suspicious' => $isSuspicious,
                'risk_score' => $riskScore,
                'risk_threshold' => self::RISK_THRESHOLD,
                'risk_factors' => $riskFactors,
                'analysis_details' => [
                    'country_analysis' => $countryAnalysis,
                    'amount_analysis' => $amountAnalysis,
                    'attempts_analysis' => $attemptsAnalysis,
                    'time_analysis' => $timeAnalysis,
                ],
                'recommendation' => $isSuspicious ? 'block' : 'approve',
                'suspicious_transaction_id' => $isSuspicious ? $suspiciousTransaction->id : null,
            ], __('messages.transaction_analysis_completed'));

        } catch (\Exception $e) {
            Log::error('Error analyzing transaction: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Analyze country mismatch between IP and card
     */
    private static function analyzeCountryMismatch(string $userIp, array $paymentData): array
    {
        try {
            // الحصول على دولة المستخدم من IP
            $userCountry = self::getCountryFromIp($userIp);

            // الحصول على دولة البطاقة من بيانات الدفع
            $cardCountry = $paymentData['card_country'] ?? null;

            $isMismatch = false;
            if ($userCountry && $cardCountry && $userCountry !== $cardCountry) {
                $isMismatch = true;
            }

            return [
                'user_country' => $userCountry,
                'card_country' => $cardCountry,
                'is_mismatch' => $isMismatch,
                'mismatch_severity' => $isMismatch ? 'high' : 'none',
            ];
        } catch (\Exception $e) {
            Log::error('Error analyzing country mismatch: ' . $e->getMessage());
            return [
                'user_country' => null,
                'card_country' => null,
                'is_mismatch' => false,
                'mismatch_severity' => 'unknown',
            ];
        }
    }

    /**
     * Analyze high amount transactions
     */
    private static function analyzeHighAmount(int $amountCents, User $user): array
    {
        $amount = $amountCents / 100;
        $userAverageOrder = $user->orders()->where('status', 'paid')->avg('total_price') ?? 0;
        $threshold = $userAverageOrder * 3; // 3 أضعاف متوسط طلبات المستخدم

        $isHigh = $amount > $threshold && $amount > 100; // أكثر من 100 ريال

        return [
            'amount' => $amount,
            'user_average' => $userAverageOrder,
            'threshold' => $threshold,
            'is_high' => $isHigh,
            'severity' => $isHigh ? ($amount > 500 ? 'critical' : 'high') : 'normal',
        ];
    }

    /**
     * Analyze multiple payment attempts
     */
    private static function analyzeMultipleAttempts(User $user, int $amountCents): array
    {
        $recentAttempts = Payment::where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->where('amount_cents', $amountCents)
            ->count();

        $isMultiple = $recentAttempts > 3;

        return [
            'recent_attempts' => $recentAttempts,
            'is_multiple' => $isMultiple,
            'severity' => $isMultiple ? ($recentAttempts > 5 ? 'critical' : 'high') : 'normal',
        ];
    }

    /**
     * Analyze unusual transaction time
     */
    private static function analyzeUnusualTime(User $user): array
    {
        $currentHour = Carbon::now()->hour;
        $userTimezone = $user->timezone ?? 'Asia/Riyadh';

        // تحليل الوقت بناءً على المنطقة الزمنية للمستخدم
        $isUnusual = $currentHour < 6 || $currentHour > 23; // خارج ساعات العمل العادية

        return [
            'current_hour' => $currentHour,
            'user_timezone' => $userTimezone,
            'is_unusual' => $isUnusual,
            'severity' => $isUnusual ? 'medium' : 'normal',
        ];
    }

    /**
     * Get country from IP address
     */
    private static function getCountryFromIp(string $ip): ?string
    {
        try {
            // استخدام خدمة GeoIP أو API خارجي
            $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}");

            if ($response->successful()) {
                $data = $response->json();
                return $data['countryCode'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error getting country from IP: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get suspicious transactions for admin review
     */
    public static function getSuspiciousTransactions(): JsonResponse
    {
        try {
            $transactions = SuspiciousTransaction::with(['payment.order.user'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'payment_id' => $transaction->payment_id,
                        'user_id' => $transaction->user_id,
                        'user_name' => $transaction->payment->order->user->name,
                        'user_email' => $transaction->payment->order->user->email,
                        'risk_score' => $transaction->risk_score,
                        'risk_factors' => json_decode($transaction->risk_factors, true),
                        'user_ip' => $transaction->user_ip,
                        'user_country' => $transaction->user_country,
                        'card_country' => $transaction->card_country,
                        'amount_cents' => $transaction->amount_cents,
                        'amount' => $transaction->amount_cents / 100,
                        'status' => $transaction->status,
                        'analyzed_at' => $transaction->analyzed_at,
                        'reviewed_at' => $transaction->reviewed_at,
                        'reviewed_by' => $transaction->reviewed_by,
                        'review_notes' => $transaction->review_notes,
                    ];
                });

            return BaseController::sendResponse([
                'suspicious_transactions' => $transactions,
                'total_count' => $transactions->count(),
                'pending_review' => $transactions->where('status', 'pending_review')->count(),
                'approved' => $transactions->where('status', 'approved')->count(),
                'blocked' => $transactions->where('status', 'blocked')->count(),
            ], __('messages.success'));
        } catch (\Exception $e) {
            Log::error('Error getting suspicious transactions: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Review suspicious transaction
     */
    public static function reviewTransaction(int $transactionId, string $decision, string $notes = null): JsonResponse
    {
        try {
            $transaction = SuspiciousTransaction::find($transactionId);

            if (!$transaction) {
                return BaseController::sendError(__('messages.transaction_not_found'), [], 404);
            }

            $reviewedBy = auth()->user()?->id;

            $transaction->update([
                'status' => $decision,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewedBy,
                'review_notes' => $notes,
            ]);

            // إذا تم حظر المعاملة، قم بإلغاء الدفع
            if ($decision === 'blocked') {
                $transaction->payment->update(['status' => 'cancelled']);
            }

            Log::info("Suspicious transaction reviewed", [
                'transaction_id' => $transactionId,
                'decision' => $decision,
                'reviewed_by' => $reviewedBy,
            ]);

            return BaseController::sendResponse([
                'transaction_id' => $transactionId,
                'decision' => $decision,
                'reviewed_at' => $transaction->reviewed_at,
                'reviewed_by' => $transaction->reviewed_by,
                'review_notes' => $transaction->review_notes,
            ], __('messages.transaction_reviewed'));
        } catch (\Exception $e) {
            Log::error('Error reviewing transaction: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Notify admins about suspicious transaction
     */
    private static function notifyAdmins(SuspiciousTransaction $transaction): void
    {
        try {
            $admins = User::role('admin')->get();

            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\SuspiciousTransactionNotification($transaction));
            }
        } catch (\Exception $e) {
            Log::error('Error notifying admins: ' . $e->getMessage());
        }
    }

    /**
     * Get suspicious transaction statistics
     */
    public static function getStats(): JsonResponse
    {
        try {
            $totalSuspicious = SuspiciousTransaction::count();
            $pendingReview = SuspiciousTransaction::where('status', 'pending_review')->count();
            $approved = SuspiciousTransaction::where('status', 'approved')->count();
            $blocked = SuspiciousTransaction::where('status', 'blocked')->count();

            $avgRiskScore = SuspiciousTransaction::avg('risk_score') ?? 0;

            $countryMismatches = SuspiciousTransaction::whereNotNull('user_country')
                ->whereNotNull('card_country')
                ->whereRaw('user_country != card_country')
                ->count();

            return BaseController::sendResponse([
                'total_suspicious_transactions' => $totalSuspicious,
                'pending_review' => $pendingReview,
                'approved' => $approved,
                'blocked' => $blocked,
                'average_risk_score' => round($avgRiskScore, 2),
                'country_mismatches' => $countryMismatches,
                'block_rate' => $totalSuspicious > 0 ? round(($blocked / $totalSuspicious) * 100, 2) : 0,
            ], __('messages.success'));
        } catch (\Exception $e) {
            Log::error('Error getting suspicious transaction stats: ' . $e->getMessage());
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }
}
