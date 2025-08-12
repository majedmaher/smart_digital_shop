<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $isUpdate = $this->route('id') !== null;
        return [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|array',
            'name.ar' => 'required|string',
            'name.en' => 'required|string',
            'image' => [
                $isUpdate ? 'nullable' : 'required',
                'file',
                'mimes:png,jpg,jpeg,webp',
                'max:2048'
            ],

            'icon' => [
                $isUpdate ? 'nullable' : 'required',
                'file',
                'mimes:png,jpg,jpeg,webp',
                'max:2048'
            ],
            // 'parent_id' => 'nullable|exists:sub_categories,id'
            'parent_id' => [
                'nullable',
                'exists:sub_categories,id',
                Rule::exists('sub_categories', 'id')->where(function ($query) {
                    // نضمن أن الأب ينتمي لنفس الـ category
                    $query->where('category_id', $this->category_id);
                }),
            ]
        ];
    }
}
