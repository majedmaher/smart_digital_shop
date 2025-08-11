<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'price' => $this->price,
            'price_before' => $this->price_before,
            'discount' => $this->discount,
            'image' => $this->image,
            'shipping_payment' => $this->shipping_payment,
        ];

        if (isset($this->codes_count)) {
            $data['codes_count'] = $this->codes_count;
        }

        return $data;
    }
}
