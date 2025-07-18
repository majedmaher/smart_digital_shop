<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CodeRequest extends FormRequest
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
            // 'user_id' => 'nullable|exists:users,id',
            'code' => 'required',
            'is_used' => 'nullable|boolean',
            'used_at' => 'nullable|date_format:Y-m-d H:i:s',
            'order_id' => 'nullable|exists:orders,id',
            'notes' => 'nullable|string'
        ];
    }
}
