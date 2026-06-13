<?php

namespace App\Http\Requests;

use App\Models\ProductVariant;
use App\Services\RentalAvailabilityService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRentalItemRequest extends FormRequest
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
            'rental_package_id' => ['nullable', 'ulid', 'exists:rental_packages,id'],
            'product_id' => ['required', 'ulid', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'ulid', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->validateVariantBelongsToProduct($validator);
                $this->validateVariantAvailability($validator);
            },
        ];
    }

    protected function validateVariantBelongsToProduct(Validator $validator): void
    {
        if (blank($this->input('product_variant_id'))) {
            return;
        }

        $variantMatchesProduct = ProductVariant::query()
            ->whereKey($this->input('product_variant_id'))
            ->where('product_id', $this->input('product_id'))
            ->exists();

        if (! $variantMatchesProduct) {
            $validator->errors()->add('product_variant_id', 'Varian produk harus sesuai dengan produk yang dipilih.');
        }
    }

    protected function validateVariantAvailability(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty() || blank($this->input('product_variant_id'))) {
            return;
        }

        $rental = $this->route('rental');
        $unavailableItems = app(RentalAvailabilityService::class)->unavailableItems(
            [$this->only(['product_variant_id', 'quantity'])],
            $rental->pickup_at,
            $rental->return_due_at,
            null,
            $this->route('rentalItem')?->id
        );

        foreach ($unavailableItems as $availability) {
            $validator->errors()->add(
                'product_variant_id',
                "Stok {$availability['product_name']} {$availability['variant_name']} tidak cukup untuk tanggal sewa yang dipilih. Tersedia {$availability['available_quantity']}, diminta {$availability['requested_quantity']}."
            );
        }
    }
}
