<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "product_title" => $this->product->title,
            "user_name" => $this->user->name,
            "code" => $this->code,
            "is_used" => $this->is_used,
            "used_at" => $this->used_at,
            "order_item_id" => $this->order_item_id,
            "notes" => $this->notes
        ];
    }
}
