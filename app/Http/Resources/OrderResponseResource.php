<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResponseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currency = strtoupper($request->header('Currency', 'SAR'));
        return [
            'id' => $this->id,
            'status' => $this->status,
            // 'vat' => currencyConverter($this->vat, $currency),
            // 'total_price' => currencyConverter($this->total_price, $currency),
            // 'discount' => currencyConverter($this->discount, $currency),
            'vat' => $this->vat,
            'total_price' => $this->total_price,
            'discount' => $this->discount,
            'user_total_price' => $this->total_price_user_currency,
            'user_discount' => $this->discount_user_currency,
            'user_vat' => $this->vat_user_currency,
            'user_currency' => $this->currency_symbol,
            // 'coupon_id'=>$this->coupon_id,
        ];
    }
}
