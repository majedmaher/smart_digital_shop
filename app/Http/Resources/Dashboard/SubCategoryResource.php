<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubCategoryResource extends JsonResource
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
            'category' => $this->category->name ?? null,
            'parent' => $this->parent->name ?? null,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'image' => $this->image
        ];
        if (isset($this->children_count)) {
            $data['children_count'] = $this->children_count;
        }
        if (isset($this->products_count)) {
            $data['products_count'] = $this->products_count;
        }
        return $data;
    }
}
