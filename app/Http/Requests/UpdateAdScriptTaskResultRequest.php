<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdScriptTaskResultRequest extends FormRequest
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
            'new_script' => ['required', 'string', 'min:10', 'max:10000'],
            'analysis' => ['required', 'string', 'min:10', 'max:10000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'new_script.required' => 'The new script is required.',
            'new_script.string' => 'The new script must be a string.',
            'new_script.min' => 'The new script must be at least 10 characters.',
            'new_script.max' => 'The new script may not be greater than 10,000 characters.',
            'analysis.required' => 'The analysis is required.',
            'analysis.string' => 'The analysis must be a string.',
            'analysis.min' => 'The analysis must be at least 10 characters.',
            'analysis.max' => 'The analysis may not be greater than 10,000 characters.',
        ];
    }
}
