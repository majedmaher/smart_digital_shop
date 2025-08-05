<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
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
            'cart' => 'required|array|min:1',
            'cart.*.product_id' => 'required|exists:products,id',
            'cart.*.quantity' => 'required|integer|min:1',
            'cart.*.shipping_data' => 'required|array',
            'coupon_code' => 'nullable|exists:coupons,code',
        ];
    }

    public function messages(): array
    {
        return [
            'cart.required' => __('messages.cart_empty'),
            'cart.array' => __('validation.array'),
            'cart.min' => __('validation.min.integer'),

            'cart.*.product_id.required' => __('validation.exists'),
            'cart.*.product_id.exists' => __('validation.exists'),

            'cart.*.quantity.required' => __('validation.required'),
            'cart.*.quantity.integer' => __('validation.numeric'),
            'cart.*.quantity.min' => __('validation.min.numeric'),

            'cart.*.shipping_data.required' => __('validation.required'),
            'cart.*.shipping_data.array' => __('validation.array'),

            'coupon_code.exists' => __('validation.exists'),
        ];
    }
}
