<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
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
            'penalty_payment_method' => ['nullable', 'string', 'in:cash,transfer,qris,debit,other'],
            'penalty_paid_at' => ['nullable', 'date'],
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
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $rental = $this->route('rental');

                if (! in_array($rental?->status, ['picked_up', 'overdue'], true)) {
                    return;
                }

                $returnedAt = Carbon::parse($this->input('returned_at'))->startOfDay();
                $returnDueAt = $rental?->return_due_at?->copy()->startOfDay();
                $isLate = $returnDueAt && $returnedAt->greaterThan($returnDueAt);
                $penaltyAmount = (float) $this->input('penalty_amount', 0);

                if ($isLate && $penaltyAmount <= 0) {
                    $validator->errors()->add('penalty_amount', 'Nominal denda wajib diisi jika barang terlambat dikembalikan.');
                }

                if ($penaltyAmount <= 0) {
                    return;
                }

                if (blank($this->input('penalty_payment_method'))) {
                    $validator->errors()->add('penalty_payment_method', 'Metode pembayaran denda wajib diisi.');
                }

                if (blank($this->input('penalty_paid_at'))) {
                    $validator->errors()->add('penalty_paid_at', 'Tanggal pembayaran denda wajib diisi.');
                }
            },
        ];
    }
}
