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
            'status' => ['required', 'string', Rule::in(['completed', 'failed'])],
            'outcome_description' => ['nullable', 'string', 'max:10000'],
            'error_message' => ['nullable', 'string', 'max:2000'],
            'additional_data' => ['nullable', 'array'],
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
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either "completed" or "failed".',
            'outcome_description.max' => 'The outcome description may not be greater than 10,000 characters.',
            'error_message.max' => 'The error message may not be greater than 2,000 characters.',
            'additional_data.array' => 'The additional data must be an array.',
        ];
    }
}
