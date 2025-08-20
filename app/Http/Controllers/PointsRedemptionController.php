<?php

namespace App\Http\Controllers;

use App\Services\WalletService;
use Illuminate\Http\JsonResponse;

class PointsRedemptionController extends Controller
{
    public function redeem(): JsonResponse
    {
        return WalletService::redeem();
    }
}
