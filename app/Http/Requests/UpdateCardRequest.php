<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCardRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'last_four' => ['nullable', 'string', 'size:4'],
            'credit_limit' => ['sometimes', 'numeric', 'min:0'],
            'closing_day' => ['sometimes', 'integer', 'min:1', 'max:31'],
            'due_day' => ['sometimes', 'integer', 'min:1', 'max:31'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }
}
