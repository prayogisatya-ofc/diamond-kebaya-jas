<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRentalItemRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Rental;
use App\Models\RentalItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class RentalItemController extends Controller
{
    public function store(StoreRentalItemRequest $request, Rental $rental): RedirectResponse
    {
        DB::transaction(function () use ($request, $rental): void {
            $rental->items()->create($this->itemAttributes($request->validated()));
            $this->refreshTotals($rental);
        });

        return redirect()->route('rentals.show', $rental)->with('success', 'Item rental berhasil ditambahkan.');
    }

    public function update(StoreRentalItemRequest $request, Rental $rental, RentalItem $rentalItem): RedirectResponse
    {
        if ($rentalItem->rental_id !== $rental->id) {
            abort(404);
        }

        if ($rental->status !== 'booked') {
            return back()->withErrors([
                'items' => 'Item hanya bisa diubah saat rental masih berstatus booking.',
            ]);
        }

        DB::transaction(function () use ($request, $rental, $rentalItem): void {
            $rentalItem->update($this->itemAttributes($request->validated()));
            $this->refreshTotals($rental);
        });

        return redirect()->route('rentals.show', $rental)->with('success', 'Item rental berhasil diperbarui.');
    }

    public function destroy(Rental $rental, RentalItem $rentalItem): RedirectResponse
    {
        if ($rentalItem->rental_id !== $rental->id) {
            abort(404);
        }

        if ($rental->status !== 'booked') {
            return back()->withErrors([
                'items' => 'Item hanya bisa dihapus saat rental masih berstatus booking.',
            ]);
        }

        DB::transaction(function () use ($rental, $rentalItem): void {
            $rentalItem->delete();
            $this->refreshTotals($rental);
        });

        return redirect()->route('rentals.show', $rental)->with('success', 'Item rental berhasil dihapus.');
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function itemAttributes(array $item): array
    {
        $product = Product::query()->findOrFail($item['product_id']);
        $variant = filled($item['product_variant_id'] ?? null)
            ? ProductVariant::query()->findOrFail($item['product_variant_id'])
            : null;
        $quantity = (int) $item['quantity'];
        $unitPrice = (float) $item['unit_price'];
        $discountAmount = (float) ($item['discount_amount'] ?? 0);

        return [
            'rental_package_id' => $item['rental_package_id'] ?? null,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'item_name_snapshot' => $product->name,
            'variant_name_snapshot' => $variant?->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => $discountAmount,
            'final_price' => max(0, ($quantity * $unitPrice) - $discountAmount),
            'notes' => $item['notes'] ?? null,
        ];
    }

    private function refreshTotals(Rental $rental): void
    {
        $rental->load('items');

        $subtotalAmount = (float) $rental->items->sum('final_price');
        $totalAmount = $subtotalAmount
            + (float) $rental->custom_adjustment_amount
            + (float) $rental->penalty_amount
            - (float) $rental->discount_amount;
        $paidAmount = (float) $rental->paid_amount;

        $rental->update([
            'subtotal_amount' => $subtotalAmount,
            'total_amount' => $totalAmount,
            'remaining_amount' => $totalAmount - $paidAmount,
            'payment_status' => $this->paymentStatus($totalAmount, $paidAmount),
        ]);
    }

    private function paymentStatus(float $totalAmount, float $paidAmount): string
    {
        if ($paidAmount <= 0) {
            return 'unpaid';
        }

        if ($paidAmount < $totalAmount) {
            return 'dp';
        }

        if ($paidAmount > $totalAmount) {
            return 'overpaid';
        }

        return 'paid';
    }
}
