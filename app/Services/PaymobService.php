<?php

namespace App\Services;

use App\Enum\OrderStatusEnum;
use App\Enum\PaymentCurrencyEnum;
use App\Enum\PaymentProviderEnum;
use App\Enum\PaymentStatusEnum;
use App\Helpers\OrderHelper;
use App\Http\Controllers\API\BaseController;
use App\Jobs\PushOrderToZohoJob;
use App\Jobs\SendCodeAfterPayment;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\PaymentSuccessNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymobService
{
    public static function createRedirectUrl(int $order_id): JsonResponse
    {
        try {
            $order = Order::findOrFail($order_id);

            if ($order->status === OrderStatusEnum::PAID->value) {
                return BaseController::sendResponse(['order_id' => $order->id], __('messages.order_paid'));
            }
            // ✅ التحقق من الأكواد أولاً
            if ($error = OrderService::validateCodeAvailability($order->items->toArray())) {
                return $error; // رجوع فوري لو فيه خلل
            }

            $billingData = self::buildBillingData();

            $integrationId = config('services.paymob.integration_id');
            $paymobPayload = [
                'amount' => (int) round($order->total_price * 100),
                'currency' => PaymentCurrencyEnum::DEFAULT_CURRENCY->value,
                'payment_methods' => [intval($integrationId)],
                'items' => OrderHelper::buildItems($order),
                'billing_data' => $billingData,
                // 'special_reference' => 'order-' . $order->id,
                'special_reference' => 'order-' . $order->id . '-' . uniqid(),
                "notification_url" => config('services.paymob.notification_url'),
                "redirection_url" => config('services.paymob.redirect_url')
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Token ' . config('services.paymob.api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://ksa.paymob.com/v1/intention', $paymobPayload);

            if ($response->failed()) {
                // Log::error('Paymob API Error', ['response' => $response->body()]);
                return BaseController::sendError(__('messages.failed_to_create_payment'), [$response->body()], 422);
            }

            $data = $response->json();
            $paymentKey = $data['payment_keys'][0]['key'];

            $result = [
                'payment_url' => config('services.paymob.iframe_url') . $paymentKey,
                'redirect_url' => $data['redirection_url'],
                // 'intention_order_id' => $data['intention_order_id'],
                // 'intention_id' => $data['id'],
                // 'client_secret' => $data['client_secret'],
            ];

            return BaseController::sendResponse($result, __('messages.payment_request_created_successfully'));
        } catch (\Throwable $th) {
            // Log::error('PaymobService Error', ['error' => $th->getMessage()]);
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }

    /**
     * استرداد مبلغ من معاملة مدفوعة
     * 
     * @param int $transaction_id معرف المعاملة من Paymob
     * @param int $amount_cents المبلغ المراد استرداده بالهللة (اختياري - إذا لم يتم تمريره سيتم استرداد المبلغ كاملاً)
     * @return JsonResponse
     */
    public static function refundTransaction(int $transaction_id, ?int $amount_cents = null): JsonResponse
    {
        try {
            $payment = Payment::where('reference', $transaction_id)->first();

            if (!$payment) {
                // return redirect()->url('https://google.com');
                return BaseController::sendError(__('messages.transaction_not_exist'), [], 404);
            }

            if ($payment->status !== PaymentStatusEnum::PAID->value) {
                // return redirect()->url('https://google.com');
                return BaseController::sendError(__('messages.unpaid_transaction_can_not_refunded'), [], 403);
            }

            // إذا لم يتم تحديد المبلغ، استرد المبلغ كاملاً من الدفعة الأصلية
            // المبلغ في payment->amount_cents هو بالهللة/السنتات بالفعل
            // $amountToRefund = $amount_cents ?? $payment->amount_cents;
            $amountToRefund = $amount_cents ? ($amount_cents * 100) : $payment->amount_cents;

            // التحقق من أن المبلغ المطلوب استرداده لا يتجاوز المبلغ الأصلي
            if ($amountToRefund > $payment->amount_cents) {
                return BaseController::sendError(__('messages.amount_refunded_greater_than_amount'), [], 403);
            }

            // إرسال طلب الاسترداد إلى Paymob
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . config('services.paymob.api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://ksa.paymob.com/api/acceptance/void_refund/refund', [
                'transaction_id' => (string) $transaction_id,
                'amount_cents' => (string) $amountToRefund // إرسال المبلغ بالهللة/السنتات مباشرة
            ]);
            if ($response->failed()) {
                // Log::error('Paymob Refund API Error', [
                //     'transaction_id' => $transaction_id,
                //     'amount_cents' => $amountToRefund,
                //     'response' => $response->body()
                // ]);
                return BaseController::sendError(__('messages.recovery_process_failed'), [], 500);
            }

            $refundData = $response->json();

            if (!isset($refundData['success']) || $refundData['success'] !== true) {
                // Log::error('Paymob Refund Failed', [
                //     'transaction_id' => $transaction_id,
                //     'response' => $refundData
                // ]);
                return BaseController::sendError(__('messages.recovery_process_failed'), [], 500);
            }

            // إنشاء سجل دفعة جديد للاسترداد
            Payment::create([
                'order_id' => $payment->order_id,
                'user_id' => $payment->user_id,
                'payment_provider' => PaymentProviderEnum::PAYMOB->value,
                'reference' => $refundData['id'],
                'payment_intention_id' => null,
                'currency' => $refundData['currency'] ?? PaymentCurrencyEnum::DEFAULT_CURRENCY->value,
                'amount_cents' => -$amountToRefund, // استخدام المبلغ الصحيح بعد التأكد من نوع العمود
                'status' =>  PaymentStatusEnum::REFUNDED->value,
                'paid_at' => now(),
                'raw_response' => $refundData,
                'parent_transaction_id' => $transaction_id,
            ]);

            // تحديث حالة الطلب إذا تم استرداد المبلغ كاملاً
            if ($amountToRefund == $payment->amount_cents) {
                $order = Order::find($payment->order_id);
                if ($order) {
                    $order->update(['status' => OrderStatusEnum::REFUNDED->value]);
                }
            }

            // Log::info('Refund Successful', [
            //     'original_transaction_id' => $transaction_id,
            //     'refund_transaction_id' => $refundData['id'],
            //     'amount_cents' => $amountToRefund,
            //     'order_id' => $payment->order_id
            // ]);

            $result = [
                'refund_transaction_id' => $refundData['id'],
                'original_transaction_id' => $transaction_id,
                'refunded_amount_cents' => $amountToRefund,
                'refunded_amount_sar' => $amountToRefund / 100,
                'status' => 'success',
                'refund_details' => $refundData
            ];

            return BaseController::sendResponse($result, __('messages.recovery_process_successfully'));
        } catch (\Throwable $th) {
            // Log::error('Refund Process Error', [
            //     'transaction_id' => $transaction_id,
            //     'amount_cents' => $amount_cents,
            //     'error' => $th->getMessage()
            // ]);
            return BaseController::sendError(__('messages.error_during_recovery_process'), [], 500);
        }
    }
    /**
     * استرداد طلب كامل بناءً على معرف الطلب
     * 
     * @param int $order_id معرف الطلب
     * @return JsonResponse
     */
    public static function refundOrder(int $order_id): JsonResponse
    {
        try {
            $order = Order::findOrFail($order_id);

            // البحث عن آخر دفعة ناجحة للطلب
            $payment = $order->payments()
                ->where('status', PaymentStatusEnum::PAID->value)
                ->where('payment_provider', PaymentProviderEnum::PAYMOB->value)
                ->latest()
                ->first();

            if (!$payment) {
                return BaseController::sendError(__('messages.no_successful_payment_order'), [], 404);
            }

            // استرداد المبلغ كاملاً
            return self::refundTransaction($payment->reference);
        } catch (\Throwable $th) {
            // Log::error('Order Refund Error', [
            //     'order_id' => $order_id,
            //     'error' => $th->getMessage()
            // ]);
            return BaseController::sendError(__('messages.error_during_request'), [], 500);
        }
    }

    /**
     * معالجة webhook من Paymob
     */
    public static function handlePaymobWebhook($request)
    {
        try {
            $data = $request->all();

            // Log::info('📥 Paymob Webhook Received', $data);

            if (!self::verifyHmac($data)) {
                // Log::warning('❌ Invalid HMAC signature');
                return BaseController::sendError('Invalid HMAC', [], 403);
                // return response()->json(['error' => 'Invalid HMAC'], 403);
            }

            // تحقق أن العملية ناجحة
            if (!isset($data['obj']['success']) || $data['obj']['success'] !== true) {
                // Log::warning('⚠️ Payment not successful');
                return BaseController::sendError(__('messages.payment_was_unsuccessful'), [], 400);
                // return response()->json(['message' => 'Payment not successful'], 400);
            }

            $obj = $data['obj'];

            // التحقق من نوع المعاملة (دفع أم استرداد)
            $isRefund = isset($obj['is_refund']) && $obj['is_refund'] === true;

            if ($isRefund) {
                return self::handleRefundWebhook($obj);
            } else {
                return self::handlePaymentWebhook($obj);
            }
        } catch (\Throwable $e) {
            // Log::error('❌ Webhook processing error', ['error' => $e->getMessage()]);
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
            // return response()->json(['error' => 'Server error'], 500);
        }
    }

    /**
     * معالجة webhook الخاص بالدفع
     */
    private static function handlePaymentWebhook($obj)
    {
        try {
            $merchantOrderId = $obj['merchant_order_id']
                ?? $obj['payment_key_claims']['extra']['merchant_order_id']
                ?? null;

            if (!$merchantOrderId || !str_starts_with($merchantOrderId, 'order-')) {
                // Log::error('❌ Invalid merchant_order_id for payment', ['merchant_order_id' => $merchantOrderId]);
                return BaseController::sendError(__('messages.invalid_order_id'), [], 400);
                // return response()->json(['error' => 'Invalid order ID'], 400);
            }
            $parts = explode('-', $merchantOrderId);
            $orderId = isset($parts[1]) ? (int) $parts[1] : null;
            // $orderId = (int) str_replace('order-', '', $merchantOrderId);
            $order = Order::with('items.product')->find($orderId);

            if (!$order) {
                // Log::error('❌ Order not found', ['order_id' => $orderId]);
                return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.order')]), [], 404);
                // return response()->json(['error' => 'Order not found'], 404);
            }

            if ($order->status === OrderStatusEnum::PAID->value) {
                // Log::info('✅ Order already paid', ['order_id' => $orderId]);
                return BaseController::sendResponse(['order_id' => $orderId], __('messages.order_paid'));
                // return response()->json(['message' => 'Already paid'], 200);
            }

            $order->payments()->create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'payment_provider' => PaymentProviderEnum::PAYMOB->value,
                'reference' => $obj['id'], // transaction ID
                'payment_intention_id' => $obj['payment_key_claims']['next_payment_intention'] ?? null,
                'currency' => $obj['currency'] ?? PaymentCurrencyEnum::DEFAULT_CURRENCY->value,
                'amount_cents' => $obj['amount_cents'],
                'status' => PaymentStatusEnum::PAID->value,
                'paid_at' => now(),
                'raw_response' => $obj,
            ]);

            $order->update(['status' => OrderStatusEnum::PAID->value]);
            $user = $order->user;


            $total = (float) $order->total_price;
            if ($total >= 100) {
                // $blocks = floor($total / 100);                   // كل 100$ = بلوك واحد
                // $pointsToAdd = (int) ($blocks * 1000);         // كل بلوك = 1000 نقطة
                $pointsToAdd = (int) ($total * 10);         // كل بلوك = 1000 نقطة
                // if ($pointsToAdd > 0) {
                $user->increment('points', $pointsToAdd);
                // }
            }


            $zohoItems = [];
            foreach ($order->items as $item) {
                $zohoItems[] = [
                    'sku'      => $item->product->code ?? 'SKU-' . $item->id,
                    'name'     => $item->product->title,
                    'price'    => (float) $item->price, // السعر الأصلي
                    'discount' => (float) $item->discount / $item->quantity, // الخصم على هذا العنصر
                    'qty'      => (int) $item->quantity,
                    'tax_rate' => (float) ($item->product->vat_rate ?? 0), // مثال: 15
                ];
            }

            $orderPayload = [
                'customer'       => ['name' => $user->name, 'email' => $user->email, 'phone' => $user->phone],
                'items'          => $zohoItems,
                'payment_method' => 'wallet',
                'transaction_id' => $obj['id'],
            ];
            PushOrderToZohoJob::dispatch($orderPayload, $order->id);


            if ($user && $user->email) {
                $user->notify(new PaymentSuccessNotification($order));
            }
            // Log::info('✅ Order already paid', ['order_id' => $order]);

            SendCodeAfterPayment::dispatch($order);

            // Log::info('💰 Payment confirmed', ['order_id' => $orderId, 'transaction_id' => $obj['id']]);

            return BaseController::sendResponse(['order_id' => $orderId, 'transaction_id' => $obj['id']], __('messages.payment_confirmed'));
            // return response()->json(['message' => 'Payment confirmed'], 200);
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [$th->getMessage()], 500);
        }
    }

    /**
     * معالجة webhook الخاص بالاسترداد
     */
    private static function handleRefundWebhook($obj)
    {
        $parentTransactionId = $obj['parent_transaction'] ?? null;

        if (!$parentTransactionId) {
            // Log::error('❌ Missing parent_transaction for refund', ['refund_id' => $obj['id']]);
            return BaseController::sendError(__('messages.missing_parent_transaction'), [], 400);
            // return response()->json(['error' => 'Missing parent transaction'], 400);
        }

        // البحث عن الدفعة الأصلية
        $originalPayment = Payment::where('reference', $parentTransactionId)->first();

        if (!$originalPayment) {
            // Log::error('❌ Original payment not found', ['parent_transaction_id' => $parentTransactionId]);
            return BaseController::sendError(__('messages.original_payment_not_found'), [], 400);
            // return response()->json(['error' => 'Original payment not found'], 404);
        }

        // إنشاء سجل الاسترداد
        Payment::create([
            'order_id' => $originalPayment->order_id,
            'user_id' => $originalPayment->user_id,
            'payment_provider' => PaymentProviderEnum::PAYMOB->value,
            'reference' => $obj['id'],
            'payment_intention_id' => null,
            'currency' => $obj['currency'] ?? PaymentCurrencyEnum::DEFAULT_CURRENCY->value,
            'amount_cents' => -$obj['amount_cents'], // مبلغ سالب
            'status' => PaymentStatusEnum::REFUNDED->value,
            'paid_at' => now(),
            'raw_response' => $obj,
            'parent_transaction_id' => $parentTransactionId,
        ]);

        $res = [
            'refund_id' => $obj['id'],
            'parent_transaction_id' => $parentTransactionId,
            'amount_cents' => $obj['amount_cents']
        ];
        // Log::info('💸 Refund confirmed', $res);

        return BaseController::sendResponse($res, __('messages.refund_confirmed'));
        // return response()->json(['message' => 'Refund confirmed'], 200);
    }

    /**
     * بناء بيانات الفوترة
     */
    private static function buildBillingData(): array
    {
        $user = auth()->user();

        return [
            'apartment' => 'N/A',
            'first_name' => $user->name ?? 'User',
            'last_name' => 'N/A',
            'street' => 'N/A',
            'building' => 'N/A',
            'phone_number' => $user->phone ?? '+966000000000',
            'city' => 'N/A',
            'country' => 'SA',
            'email' => $user->email ?? 'test@example.com',
            'floor' => 'N/A',
            'state' => 'N/A',
        ];
    }

    /**
     * التحقق من صحة HMAC حسب توثيق Paymob الرسمي
     */
    private static function verifyHmac(array $data): bool
    {
        try {
            $secret = config('services.paymob.hmac');

            if (!isset($data['hmac']) || !isset($data['obj'])) {
                return false;
            }

            $obj = $data['obj'];

            $orderedKeys = [
                'amount_cents',
                'created_at',
                'currency',
                'error_occurred',
                'has_parent_transaction',
                'id',
                'integration_id',
                'is_3d_secure',
                'is_auth',
                'is_capture',
                'is_refunded',
                'is_standalone_payment',
                'is_voided',
                'order',
                'owner',
                'pending',
                'source_data_pan',
                'source_data_sub_type',
                'source_data_type',
                'success'
            ];

            $concatenated = '';

            foreach ($orderedKeys as $key) {
                $value = '';

                switch ($key) {
                    case 'order':
                        $value = $obj['order']['id'] ?? '';
                        break;
                    case 'source_data_pan':
                        $value = $obj['source_data']['pan'] ?? '';
                        break;
                    case 'source_data_sub_type':
                        $value = $obj['source_data']['sub_type'] ?? '';
                        break;
                    case 'source_data_type':
                        $value = $obj['source_data']['type'] ?? '';
                        break;
                    case 'error_occurred':
                        $value = $obj['error_occurred'] ?? $obj['error_occured'] ?? '';
                        break;
                    default:
                        $value = $obj[$key] ?? '';
                        break;
                }

                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }

                $concatenated .= $value;
            }

            $generatedHmac = hash_hmac('sha512', $concatenated, $secret);

            return hash_equals($generatedHmac, $data['hmac']);
        } catch (\Throwable $e) {
            // Log::error('HMAC Verification Error', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
