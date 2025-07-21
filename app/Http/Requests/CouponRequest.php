<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CouponRequest extends FormRequest
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
            'code' => 'required|string|unique:coupons,code',
            'value' => 'required|numeric|min:0.01',
            'type' => 'required|in:fixed,percent',
            'usage_limit' => 'nullable|numeric|min:1',
            'expires_from' => 'nullable|date|after_or_equal:today',
            'expires_at' => 'nullable|date|after_or_equal:today',
        ];
    }
}
