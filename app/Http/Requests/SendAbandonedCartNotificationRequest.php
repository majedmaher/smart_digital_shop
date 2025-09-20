<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendAbandonedCartNotificationRequest extends FormRequest
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
            'order_id' => [
                'required',
                'integer',
                'exists:orders,id'
            ],
            'notification_type' => [
                'nullable',
                'string',
                Rule::in(['reminder', 'urgent', 'final'])
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'order_id.required' => __('validation.required', ['attribute' => 'order_id']),
            'order_id.integer' => __('validation.integer', ['attribute' => 'order_id']),
            'order_id.exists' => __('validation.exists', ['attribute' => 'order_id']),
            'notification_type.string' => __('validation.string', ['attribute' => 'notification_type']),
            'notification_type.in' => __('validation.in', ['attribute' => 'notification_type']),
        ];
    }
}
