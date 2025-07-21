<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'name' => $this->name,
            'icon' => $this->icon
        ];
        if ($sub_categories = $this->subCategories) {
            $data['sub_categories'] = SubCategoryResource::collection($sub_categories);
        }
        return $data;
    }
}
