<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
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
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'type' => ['sometimes', 'string', 'in:INCOME,EXPENSE'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'transaction_date' => ['sometimes', 'date'],
            'card_id' => ['nullable', 'integer', 'exists:cards,id'],
            'payment_method' => ['nullable', 'string', 'in:CASH,PIX,DEBIT,CREDIT'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_paid' => ['sometimes', 'boolean'],
        ];
    }
}
