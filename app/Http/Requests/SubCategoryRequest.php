<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubCategoryRequest extends FormRequest
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
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|array',
            'name.ar' => 'required|string',
            'name.en' => 'required|string',
            'image' => 'required|file|mimes:png,jpg,jpeg|max:2048',
            'parent_id' => 'nullable|exists:sub_categories,id'
        ];
    }
}
