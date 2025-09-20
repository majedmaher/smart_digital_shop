<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\ReviewSuspiciousTransactionRequest;
use App\Http\Requests\AnalyzeTransactionRequest;
use App\Services\SuspiciousTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuspiciousTransactionController extends Controller
{
    /**
     * Get all suspicious transactions
     */
    public function getSuspiciousTransactions(): JsonResponse
    {
        return SuspiciousTransactionService::getSuspiciousTransactions();
    }

    /**
     * Analyze a specific transaction
     */
    public function analyzeTransaction(AnalyzeTransactionRequest $request): JsonResponse
    {
        $data = $request->validated();
        return SuspiciousTransactionService::analyzeTransaction(
            $data['payment_id'],
            $data['user_ip'],
            $data['payment_data'] ?? []
        );
    }

    /**
     * Review suspicious transaction
     */
    public function reviewTransaction(ReviewSuspiciousTransactionRequest $request): JsonResponse
    {
        $data = $request->validated();
        return SuspiciousTransactionService::reviewTransaction(
            $data['transaction_id'],
            $data['decision'],
            $data['notes'] ?? null
        );
    }

    /**
     * Get suspicious transaction statistics
     */
    public function getStats(): JsonResponse
    {
        return SuspiciousTransactionService::getStats();
    }

    /**
     * Get suspicious transaction by ID
     */
    public function getSuspiciousTransaction(Request $request): JsonResponse
    {
        try {
            $transactionId = $request->route('id');
            $transaction = \App\Models\SuspiciousTransaction::with(['payment.order.user', 'reviewer'])
                ->find($transactionId);

            if (!$transaction) {
                return BaseController::sendError(__('messages.transaction_not_found'), [], 404);
            }

            return BaseController::sendResponse([
                'id' => $transaction->id,
                'payment_id' => $transaction->payment_id,
                'user_id' => $transaction->user_id,
                'user_name' => $transaction->user->name,
                'user_email' => $transaction->user->email,
                'user_phone' => $transaction->user->phone,
                'risk_score' => $transaction->risk_score,
                'risk_level' => $transaction->risk_level,
                'risk_level_label' => $transaction->risk_level_label,
                'risk_factors' => $transaction->risk_factors,
                'user_ip' => $transaction->user_ip,
                'user_country' => $transaction->user_country,
                'card_country' => $transaction->card_country,
                'amount_cents' => $transaction->amount_cents,
                'formatted_amount' => $transaction->formatted_amount,
                'status' => $transaction->status,
                'status_label' => $transaction->status_label,
                'analyzed_at' => $transaction->analyzed_at,
                'reviewed_at' => $transaction->reviewed_at,
                'reviewed_by' => $transaction->reviewed_by,
                'reviewer_name' => $transaction->reviewer?->name,
                'review_notes' => $transaction->review_notes,
                'has_country_mismatch' => $transaction->hasCountryMismatch(),
                'country_mismatch_details' => $transaction->getCountryMismatchDetails(),
                'order_details' => [
                    'id' => $transaction->payment->order->id,
                    'status' => $transaction->payment->order->status,
                    'total_price' => $transaction->payment->order->total_price,
                    'created_at' => $transaction->payment->order->created_at,
                ],
            ], __('messages.success'));
        } catch (\Exception $e) {
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Bulk review transactions
     */
    public function bulkReview(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'transaction_ids' => 'required|array|min:1',
                'transaction_ids.*' => 'integer|exists:suspicious_transactions,id',
                'decision' => 'required|in:approved,blocked',
                'notes' => 'nullable|string|max:1000',
            ]);

            $transactionIds = $request->transaction_ids;
            $decision = $request->decision;
            $notes = $request->notes;

            $processedCount = 0;
            $errors = [];

            foreach ($transactionIds as $transactionId) {
                try {
                    SuspiciousTransactionService::reviewTransaction($transactionId, $decision, $notes);
                    $processedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Transaction {$transactionId}: " . $e->getMessage();
                }
            }

            return BaseController::sendResponse([
                'total_transactions' => count($transactionIds),
                'processed_count' => $processedCount,
                'errors' => $errors,
                'decision' => $decision,
                'notes' => $notes,
            ], __('messages.bulk_review_completed'));
        } catch (\Exception $e) {
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Get risk analysis settings
     */
    public function getSettings(): JsonResponse
    {
        try {
            $settings = [
                'risk_threshold' => 70,
                'country_mismatch_penalty' => 50,
                'high_amount_penalty' => 30,
                'multiple_attempts_penalty' => 40,
                'unusual_time_penalty' => 20,
                'risk_levels' => [
                    'critical' => ['min' => 90, 'label' => 'حرج'],
                    'high' => ['min' => 70, 'label' => 'عالي'],
                    'medium' => ['min' => 50, 'label' => 'متوسط'],
                    'low' => ['min' => 0, 'label' => 'منخفض'],
                ],
                'auto_block_threshold' => 90,
                'notification_enabled' => true,
                'ip_analysis_enabled' => true,
                'country_analysis_enabled' => true,
            ];

            return BaseController::sendResponse($settings, __('messages.success'));
        } catch (\Exception $e) {
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }

    /**
     * Export suspicious transactions
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'format' => 'required|in:csv,excel',
                'status' => 'nullable|in:pending_review,approved,blocked',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ]);

            $query = \App\Models\SuspiciousTransaction::with(['payment.order.user', 'reviewer']);

            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $transactions = $query->get();

            // Generate export data
            $exportData = $transactions->map(function ($transaction) {
                return [
                    'ID' => $transaction->id,
                    'Payment ID' => $transaction->payment_id,
                    'User Name' => $transaction->user->name,
                    'User Email' => $transaction->user->email,
                    'Risk Score' => $transaction->risk_score,
                    'Risk Level' => $transaction->risk_level_label,
                    'Amount' => $transaction->formatted_amount,
                    'User IP' => $transaction->user_ip,
                    'User Country' => $transaction->user_country,
                    'Card Country' => $transaction->card_country,
                    'Status' => $transaction->status_label,
                    'Analyzed At' => $transaction->analyzed_at->format('Y-m-d H:i:s'),
                    'Reviewed At' => $transaction->reviewed_at?->format('Y-m-d H:i:s'),
                    'Reviewer' => $transaction->reviewer?->name,
                    'Review Notes' => $transaction->review_notes,
                ];
            });

            return BaseController::sendResponse([
                'export_data' => $exportData,
                'total_records' => $exportData->count(),
                'format' => $request->format,
                'exported_at' => now()->format('Y-m-d H:i:s'),
            ], __('messages.export_completed'));
        } catch (\Exception $e) {
            return BaseController::sendError(__('messages.error_occurred'), [], 500);
        }
    }
}
