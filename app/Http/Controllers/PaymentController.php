<?php

namespace App\Http\Controllers;

use App\Services\PaymobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function handlePaymobWebhook(Request $request): JsonResponse
    {
        return PaymobService::handlePaymobWebhook($request);
    }
    public function result(Request $request): JsonResponse
    {
        return response()->json($request);
    }
}
