<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecurringTransactionRequest extends FormRequest
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
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'type' => ['sometimes', 'required', 'string', 'in:INCOME,EXPENSE'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'frequency' => ['sometimes', 'required', 'string', 'in:WEEKLY,MONTHLY,YEARLY'],
            'day_of_month' => [
                'nullable',
                'integer',
                'min:1',
                'max:31',
                Rule::requiredIf(function () {
                    return $this->input('frequency') === 'MONTHLY';
                }),
            ],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'card_id' => [
                'nullable',
                'integer',
                'exists:cards,id',
            ],
            'payment_method' => [
                'nullable',
                'string',
                'in:CASH,PIX,DEBIT,CREDIT',
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'card_description' => ['nullable', 'string', 'max:1000'],
            'debtor_id' => ['nullable', 'integer', 'exists:debtors,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
