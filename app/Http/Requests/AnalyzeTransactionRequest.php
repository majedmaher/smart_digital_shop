<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnalyzeTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'payment_id' => [
                'required',
                'integer',
                'exists:payments,id'
            ],
            'user_ip' => [
                'required',
                'ip'
            ],
            'payment_data' => [
                'nullable',
                'array'
            ],
            'payment_data.card_country' => [
                'nullable',
                'string',
                'size:2'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'payment_id.required' => __('validation.required', ['attribute' => 'payment_id']),
            'payment_id.integer' => __('validation.integer', ['attribute' => 'payment_id']),
            'payment_id.exists' => __('validation.exists', ['attribute' => 'payment_id']),
            'user_ip.required' => __('validation.required', ['attribute' => 'user_ip']),
            'user_ip.ip' => __('validation.ip', ['attribute' => 'user_ip']),
            'payment_data.array' => __('validation.array', ['attribute' => 'payment_data']),
            'payment_data.card_country.string' => __('validation.string', ['attribute' => 'card_country']),
            'payment_data.card_country.size' => __('validation.size.string', ['attribute' => 'card_country', 'size' => 2]),
        ];
    }
}
