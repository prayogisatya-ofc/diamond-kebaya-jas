<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PickUpRentalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'guarantee_type' => ['nullable', 'string', 'in:ktp,sim'],
            'payment_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'payment_method' => ['nullable', 'string', 'in:cash,transfer,qris,debit,other'],
            'paid_at' => ['nullable', 'date'],
            'payment_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $rental = $this->route('rental');

                if ($rental?->status !== 'booked') {
                    return;
                }

                if (blank($rental?->guarantee_type) && blank($this->input('guarantee_type'))) {
                    $validator->errors()->add('guarantee_type', 'Pilih jaminan KTP atau SIM sebelum barang ditandai diambil.');
                }

                $remainingAmount = (float) ($rental?->remaining_amount ?? 0);

                if ($remainingAmount <= 0) {
                    return;
                }

                $paymentAmount = (float) $this->input('payment_amount', 0);

                if ($paymentAmount < $remainingAmount) {
                    $validator->errors()->add('payment_amount', 'Barang hanya bisa diambil jika sisa pembayaran dilunasi.');
                }

                if (blank($this->input('payment_method'))) {
                    $validator->errors()->add('payment_method', 'Metode pelunasan wajib diisi.');
                }

                if (blank($this->input('paid_at'))) {
                    $validator->errors()->add('paid_at', 'Tanggal pelunasan wajib diisi.');
                }
            },
        ];
    }
}
