<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FaqRequest extends FormRequest
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
            'question' => 'required|array',
            'question.ar' => 'required|string',
            'question.en' => 'required|string',
            'answer' => 'required|array',
            'answer.ar' => 'required|string',
            'answer.en' => 'required|string',
        ];
    }
}
