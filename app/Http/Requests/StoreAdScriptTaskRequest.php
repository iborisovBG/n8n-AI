<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdScriptTaskRequest extends FormRequest
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
            'reference_script' => ['required', 'string', 'min:10', 'max:10000'],
            'outcome_description' => ['required', 'string', 'min:10', 'max:10000'],
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
            'reference_script.required' => 'The reference script is required.',
            'reference_script.string' => 'The reference script must be a string.',
            'reference_script.min' => 'The reference script must be at least 10 characters.',
            'reference_script.max' => 'The reference script may not be greater than 10,000 characters.',
            'outcome_description.required' => 'The outcome description is required.',
            'outcome_description.string' => 'The outcome description must be a string.',
            'outcome_description.min' => 'The outcome description must be at least 10 characters.',
            'outcome_description.max' => 'The outcome description may not be greater than 10,000 characters.',
        ];
    }
}
