<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RatingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'stars' => 'required|integer|min:1|max:5',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:png,jpg, jpeg|max:2048',
            'show_name' => 'nullable|boolean'
        ];
    }
}
