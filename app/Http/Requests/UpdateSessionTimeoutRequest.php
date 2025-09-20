<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSessionTimeoutRequest extends FormRequest
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
            'site_status' => [
                'required',
                'string',
                Rule::in(['demo', 'live'])
            ],
            'timeout_minutes' => [
                'required',
                'integer',
                'min:30', // minimum 30 minutes
                'max:480' // maximum 8 hours
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'site_status.required' => __('validation.required', ['attribute' => 'site_status']),
            'site_status.string' => __('validation.string', ['attribute' => 'site_status']),
            'site_status.in' => __('validation.in', ['attribute' => 'site_status']),
            'timeout_minutes.required' => __('validation.required', ['attribute' => 'timeout_minutes']),
            'timeout_minutes.integer' => __('validation.integer', ['attribute' => 'timeout_minutes']),
            'timeout_minutes.min' => __('validation.min.numeric', ['attribute' => 'timeout_minutes', 'min' => 30]),
            'timeout_minutes.max' => __('validation.max.numeric', ['attribute' => 'timeout_minutes', 'max' => 480]),
        ];
    }
}
