<?php

namespace App\Http\Middleware;

use App\Services\SuspiciousTransactionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SuspiciousTransactionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware for certain routes
        if ($this->shouldSkipMiddleware($request)) {
            return $next($request);
        }

        // Check if this is a payment request
        if ($this->isPaymentRequest($request)) {
            $response = $next($request);

            // Analyze transaction after payment processing
            if ($response->getStatusCode() === 200) {
                $this->analyzeTransactionAfterPayment($request, $response);
            }

            return $response;
        }

        return $next($request);
    }

    /**
     * Determine if middleware should be skipped for this request
     */
    private function shouldSkipMiddleware(Request $request): bool
    {
        $skipRoutes = [
            'api/auth/login',
            'api/auth/register',
            'api/auth/logout',
            'api/suspicious-transactions',
            'api/admin',
        ];

        $currentRoute = $request->route()?->uri();

        foreach ($skipRoutes as $skipRoute) {
            if (str_contains($currentRoute, $skipRoute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this is a payment request
     */
    private function isPaymentRequest(Request $request): bool
    {
        return $request->is('api/order/pay') && $request->isMethod('POST');
    }

    /**
     * Analyze transaction after payment processing
     */
    private function analyzeTransactionAfterPayment(Request $request, Response $response): void
    {
        try {
            $responseData = json_decode($response->getContent(), true);

            // Check if payment was successful and we have payment data
            if (isset($responseData['success']) && $responseData['success'] && isset($responseData['data'])) {
                $paymentData = $responseData['data'];

                // Extract payment ID from response
                $paymentId = $this->extractPaymentId($paymentData);

                if ($paymentId) {
                    // Get user IP
                    $userIp = $request->ip();

                    // Extract card country from payment data if available
                    $cardCountry = $this->extractCardCountry($request);

                    // Analyze transaction
                    SuspiciousTransactionService::analyzeTransaction(
                        $paymentId,
                        $userIp,
                        ['card_country' => $cardCountry]
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in suspicious transaction middleware: ' . $e->getMessage());
        }
    }

    /**
     * Extract payment ID from response data
     */
    private function extractPaymentId(array $data): ?int
    {
        // Try different possible keys for payment ID
        $possibleKeys = ['payment_id', 'id', 'transaction_id', 'order_id'];

        foreach ($possibleKeys as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                return (int) $data[$key];
            }
        }

        return null;
    }

    /**
     * Extract card country from request data
     */
    private function extractCardCountry(Request $request): ?string
    {
        // Try to get card country from request data
        $cardCountry = $request->input('card_country')
                     ?? $request->input('billing_data.country')
                     ?? $request->input('payment_data.card_country');

        return $cardCountry;
    }
}
