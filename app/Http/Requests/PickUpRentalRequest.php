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
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $rental = $this->route('rental');

                if (blank($rental?->guarantee_type) && blank($this->input('guarantee_type'))) {
                    $validator->errors()->add('guarantee_type', 'Pilih jaminan KTP atau SIM sebelum barang ditandai diambil.');
                }
            },
        ];
    }
}
