<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResponseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $currency = strtoupper($request->header('Currency', 'SAR'));
        return [
            'id' => $this->id,
            'product_title' => $this->product->title,
            'quantity' => $this->quantity,
            'proof_file' => $this->proof_file,
            'shipping_method' => $this->shipping_method,
            'shipping_data' => $this->shipping_data,
            'total_price' => $this->total_price_user_currency,
            'discount' => $this->discount_user_currency,
            'vat' => $this->vat_user_currency,
            'currency_code' => $this->currency_symbol
        ];
    }
}
