<?php

namespace App\Http\Requests;

use App\Enum\SocialProviderEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocialAuthRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'provider' => [
                'required',
                'string',
                Rule::in(SocialProviderEnum::all())
            ],
            'access_token' => [
                'required',
                'string',
                'min:10'
            ],
            'device_type' => [
                'nullable',
                'string',
                Rule::in(['ios', 'android', 'web'])
            ],
            'device_id' => [
                'nullable',
                'string',
                'max:255'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'provider.required' => __('validation.required', ['attribute' => 'provider']),
            'provider.string' => __('validation.string', ['attribute' => 'provider']),
            'provider.in' => __('validation.in', ['attribute' => 'provider']),
            'access_token.required' => __('validation.required', ['attribute' => 'access_token']),
            'access_token.string' => __('validation.string', ['attribute' => 'access_token']),
            'access_token.min' => __('validation.min.string', ['attribute' => 'access_token', 'min' => 10]),
            'device_type.string' => __('validation.string', ['attribute' => 'device_type']),
            'device_type.in' => __('validation.in', ['attribute' => 'device_type']),
            'device_id.string' => __('validation.string', ['attribute' => 'device_id']),
            'device_id.max' => __('validation.max.string', ['attribute' => 'device_id', 'max' => 255]),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'provider' => 'المزود',
            'access_token' => 'رمز الوصول',
            'device_type' => 'نوع الجهاز',
            'device_id' => 'معرف الجهاز',
        ];
    }
}
