<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->show_name == 1 ? $this->user_id : null,
            // 'product_id'=> 1,
            'stars' => $this->star,
            'comment' => $this->comment,
        ];
    }
}
