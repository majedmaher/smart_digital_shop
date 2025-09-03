<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
            'category_id' => 'nullable|exists:categories,id',
            'sub_category_id' => 'exists:sub_categories,id',
            'title' => 'required|array',
            'title.ar' => 'required|string',
            'title.en' => 'required|string',
            'content' => 'required|array',
            'content.ar' => 'required|string',
            'content.en' => 'required|string',
            'description' => 'required|array',
            'description.ar' => 'required|string',
            'description.en' => 'required|string',
            'terms_and_conditions' => 'required|array',
            'terms_and_conditions.ar' => 'required|string',
            'terms_and_conditions.en' => 'required|string',
            'price' => 'required',
            'discount' => 'nullable|numeric|between:0,100',
            'vat_rate' => 'required|numeric|between:0,100',
            'shipping_payment' => 'required|in:code,account_id,multi_id,access',
            'image' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:2048',
        ];
    }
}
