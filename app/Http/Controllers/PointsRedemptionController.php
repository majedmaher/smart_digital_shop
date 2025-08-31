<?php

namespace App\Http\Controllers;

use App\Enum\PaymentCurrencyEnum;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PointsRedemptionController extends Controller
{
    public function redeem(Request $request): JsonResponse
    {
        return WalletService::redeem($request->header('Currency', PaymentCurrencyEnum::DEFAULT_CURRENCY->value));
    }
}
