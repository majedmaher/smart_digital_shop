<?php

namespace App\Http\Resources;

use App\Enum\PaymentCurrencyEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->type,
            'amount' => currencyConverter($this->amount, $request->header('Currency', PaymentCurrencyEnum::DEFAULT_CURRENCY->value)),
            'type' => $this->type,
        ];
    }
}
