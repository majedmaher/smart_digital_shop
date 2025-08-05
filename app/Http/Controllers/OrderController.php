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
}
