<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewSuspiciousTransactionRequest extends FormRequest
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
            'transaction_id' => [
                'required',
                'integer',
                'exists:suspicious_transactions,id'
            ],
            'decision' => [
                'required',
                'string',
                Rule::in(['approved', 'blocked'])
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'transaction_id.required' => __('validation.required', ['attribute' => 'transaction_id']),
            'transaction_id.integer' => __('validation.integer', ['attribute' => 'transaction_id']),
            'transaction_id.exists' => __('validation.exists', ['attribute' => 'transaction_id']),
            'decision.required' => __('validation.required', ['attribute' => 'decision']),
            'decision.string' => __('validation.string', ['attribute' => 'decision']),
            'decision.in' => __('validation.in', ['attribute' => 'decision']),
            'notes.string' => __('validation.string', ['attribute' => 'notes']),
            'notes.max' => __('validation.max.string', ['attribute' => 'notes', 'max' => 1000]),
        ];
    }
}
