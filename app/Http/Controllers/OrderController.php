<?php

namespace App\Http\Controllers;

use App\Enum\PaymentProviderEnum;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\OrderRequest;
use App\Services\OrderService;
use App\Services\PaymobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function getAdminOrders(): JsonResponse
    {
        return OrderService::getAdminOrders();
    }
    public function getOrderItems($id): JsonResponse
    {
        return OrderService::getOrderItems($id);
    }
    public function orderStatistics(): JsonResponse
    {
        return OrderService::orderStatistics();
    }
    public function ordersCountStats(): JsonResponse
    {
        return OrderService::ordersCountStats();
    }
    public function ordersCountStatsManual(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => 'required|date|before_or_equal:today|before_or_equal:end_date',  // start_date يجب أن يكون قبل أو يساوي اليوم وأيضًا قبل أو يساوي end_date
            'end_date' => 'required|date|before_or_equal:today|after_or_equal:start_date',  // end_date يجب أن يكون قبل أو يساوي اليوم وأيضًا بعد أو يساوي start_date
        ]);

        return OrderService::ordersCountStatsManual($data['start_date'], $data['end_date']);
    }

    public function store(OrderRequest $request): JsonResponse
    {
        $currency = strtoupper($request->header('Currency', 'SAR'));
        return OrderService::store($request->validated(), $currency);
    }

    public function pay(Request $request): JsonResponse
    {
        if ($request->payment_gateway !== PaymentProviderEnum::PAYMOB->value) {
            return BaseController::sendError(__('messages.invalid_payment_gateway'), [], 400);
        }

        return PaymobService::createRedirectUrl($request->order_id);
    }

    public function refundTransaction(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|integer',
            'amount_cents' => 'nullable|integer|min:1'
        ]);

        return PaymobService::refundTransaction(
            $request->transaction_id,
            $request->amount_cents
        );
    }

    /**
     * استرداد طلب كامل
     */
    public function refundOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id'
        ]);

        return PaymobService::refundOrder($request->order_id);
    }

    public function uploadProofFile(Request $request)
    {
        $data = $request->validate([
            'order_item_id' => 'required|integer|exists:order_items,id',
            'proof_file' => 'required|file',
        ]);
        return OrderService::uploadProofFile($data);
    }
}
