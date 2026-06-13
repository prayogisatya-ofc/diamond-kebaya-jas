<?php

namespace App\Http\Requests;

use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRentalPackageRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'package_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'is_active' => ['sometimes', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'ulid', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'ulid', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'items.*.default_item_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'items.*.is_optional' => ['sometimes', 'boolean'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateVariantBelongsToProduct($validator);
            },
        ];
    }

    protected function validateVariantBelongsToProduct(Validator $validator): void
    {
        foreach ($this->input('items', []) as $index => $item) {
            if (blank($item['product_variant_id'] ?? null)) {
                continue;
            }

            $variantMatchesProduct = ProductVariant::query()
                ->whereKey($item['product_variant_id'])
                ->where('product_id', $item['product_id'] ?? null)
                ->exists();

            if (! $variantMatchesProduct) {
                $validator->errors()->add(
                    "items.{$index}.product_variant_id",
                    'Varian produk harus sesuai dengan produk yang dipilih.'
                );
            }
        }
    }
}
