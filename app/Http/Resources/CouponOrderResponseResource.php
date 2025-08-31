<?php

namespace App\Http\Resources;

use App\Enum\PaymentCurrencyEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponOrderResponseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currency = strtoupper($request->header('Currency', PaymentCurrencyEnum::DEFAULT_CURRENCY->value));
        return [
            "product_id" => $this['product_id'],
            "quantity" => $this['quantity'],
            "shipping_data" => $this['shipping_data'],
            "price" => $this['price'] ? currencyConverter($this['price'], $currency, 2) : null,
            "category_id" => $this['category_id'],
            "sub_category_id" => $this['sub_category_id'],
            "discount" => $this['discount'] ? currencyConverter($this['discount'], $currency, 2) : null,
            "final_price" => $this['final_price'] ? currencyConverter($this['final_price'], $currency, 2) : null,
        ];
    }
}
