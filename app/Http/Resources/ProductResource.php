<?php

namespace App\Http\Resources;

use App\Enum\PaymentCurrencyEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use function PHPUnit\Framework\isEmpty;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currency = strtoupper($request->header('Currency', PaymentCurrencyEnum::DEFAULT_CURRENCY->value));
        // $currency = Number::defaultCurrency();

        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'sub_category_id' => $this->sub_category_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'description' => $this->description,
            'image' => $this->image,
            'price_before' => $this->price_before ? currencyConverter($this->price_before, $currency, 2) : null,
            'price' => $this->price ? currencyConverter($this->price, $currency, 2) : null,
            'discount' => $this->discount ? currencyConverter($this->discount, $currency, 2) : null,
            'vat_rate' => $this->vat_rate,
            'points' => $this->price >= 100 ? $this->price * 10 : 0,
            'shipping_payment' => $this->shipping_payment,
            'ratings' => $this->ratings ? RatingResource::collection($this->ratings) : null
        ];
    }
}
