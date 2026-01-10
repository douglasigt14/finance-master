<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecurringTransactionRequest extends FormRequest
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
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'type' => ['required', 'string', 'in:INCOME,EXPENSE'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'frequency' => ['required', 'string', 'in:WEEKLY,MONTHLY,YEARLY'],
            'day_of_month' => [
                'nullable',
                'integer',
                'min:1',
                'max:31',
                Rule::requiredIf(function () {
                    return $this->input('frequency') === 'MONTHLY';
                }),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'card_id' => [
                'nullable',
                'integer',
                'exists:cards,id',
                Rule::requiredIf(function () {
                    return $this->input('payment_method') === 'CREDIT';
                }),
            ],
            'payment_method' => [
                'nullable',
                'string',
                'in:CASH,PIX,DEBIT,CREDIT',
                Rule::requiredIf(function () {
                    return $this->input('type') === 'EXPENSE';
                }),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'card_description' => ['nullable', 'string', 'max:1000'],
            'debtor_id' => ['nullable', 'integer', 'exists:debtors,id'],
        ];
    }
}
