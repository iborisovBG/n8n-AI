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
            'reference_script' => ['required', 'string', 'max:10000'],
            'config' => ['nullable', 'array'],
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
            'reference_script.max' => 'The reference script may not be greater than 10,000 characters.',
            'config.array' => 'The config must be an array.',
        ];
    }
}
