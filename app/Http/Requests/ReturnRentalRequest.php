<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReturnRentalRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'returned_at' => ['required', 'date'],
            'penalty_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'pay_penalty_now' => ['sometimes', 'boolean'],
            'penalty_payment_method' => [
                Rule::requiredIf(fn (): bool => $this->boolean('pay_penalty_now')),
                'nullable',
                Rule::in(['cash', 'transfer', 'qris', 'debit', 'other']),
            ],
            'penalty_paid_at' => [
                Rule::requiredIf(fn (): bool => $this->boolean('pay_penalty_now')),
                'nullable',
                'date',
            ],
            'penalty_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->boolean('pay_penalty_now') && (float) $this->input('penalty_amount', 0) <= 0) {
                    $validator->errors()->add(
                        'penalty_amount',
                        'Nominal denda wajib lebih dari 0 jika denda langsung dibayar.'
                    );
                }
            },
        ];
    }
}
