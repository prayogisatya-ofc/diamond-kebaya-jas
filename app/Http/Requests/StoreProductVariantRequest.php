<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductVariantRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'rental_price' => $this->normalizeIntegerPrice($this->input('rental_price')),
        ]);
    }

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
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('product_variants', 'sku')],
            'name' => ['required', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'rental_price' => ['nullable', 'integer', 'min:0', 'max:9999999999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    private function normalizeIntegerPrice(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^\d+\.(\d{1,2})$/', $trimmed, $matches) === 1) {
            return $matches[1] === '00'
                ? explode('.', $trimmed)[0]
                : $trimmed;
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $trimmed) === 1) {
            return str_replace('.', '', $trimmed);
        }

        return $trimmed;
    }
}
