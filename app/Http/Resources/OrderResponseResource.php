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
            'total_price' => currencyConverter($this->total_price, $currency),
            'discount' => currencyConverter($this->discount, $currency)
            // 'coupon_id'=>$this->coupon_id,
        ];
    }
}
