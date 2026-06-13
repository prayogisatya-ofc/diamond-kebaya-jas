<?php

namespace App\Http\Requests;

use App\Models\ProductVariant;
use App\Services\RentalAvailabilityService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRentalRequest extends FormRequest
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
            'customer_mode' => ['required', 'string', 'in:existing,new'],
            'customer_id' => ['nullable', 'required_if:customer_mode,existing', 'ulid', 'exists:customers,id'],
            'new_customer.name' => ['nullable', 'required_if:customer_mode,new', 'string', 'max:255'],
            'new_customer.whatsapp_number' => ['nullable', 'required_if:customer_mode,new', 'string', 'max:30'],
            'new_customer.notes' => ['nullable', 'string'],
            'guarantee_type' => ['nullable', 'string', 'in:ktp,sim'],
            'pickup_at' => ['required', 'date'],
            'return_due_at' => ['required', 'date', 'after:pickup_at'],
            'notes' => ['nullable', 'string'],
            'custom_total_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'initial_payment_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'initial_payment_method' => ['nullable', 'string', 'in:cash,transfer,qris,debit,other'],
            'initial_payment_notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.rental_package_id' => ['nullable', 'ulid', 'exists:rental_packages,id'],
            'items.*.product_id' => ['required', 'ulid', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'ulid', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'items.*.notes' => ['nullable', 'string'],
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
                $this->validateInitialPaymentMethod($validator);
                $this->validateVariantAvailability($validator);
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

    protected function validateInitialPaymentMethod(Validator $validator): void
    {
        if ((float) $this->input('initial_payment_amount', 0) <= 0) {
            return;
        }

        if (filled($this->input('initial_payment_method'))) {
            return;
        }

        $validator->errors()->add('initial_payment_method', 'Metode pembayaran wajib diisi jika DP awal diinput.');
    }

    protected function validateVariantAvailability(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $unavailableItems = app(RentalAvailabilityService::class)->unavailableItems(
            $this->input('items', []),
            $this->input('pickup_at'),
            $this->input('return_due_at')
        );

        foreach ($unavailableItems as $availability) {
            $validator->errors()->add(
                "items.{$availability['first_item_index']}.product_variant_id",
                "Stok {$availability['product_name']} {$availability['variant_name']} tidak cukup untuk tanggal sewa yang dipilih. Tersedia {$availability['available_quantity']}, diminta {$availability['requested_quantity']}."
            );
        }
    }
}
