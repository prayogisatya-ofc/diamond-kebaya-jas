<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\RentalItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class RentalAvailabilityService
{
    /**
     * @var array<int, string>
     */
    private const BLOCKING_STATUSES = ['booked', 'picked_up', 'overdue'];

    /**
     * @param  array<int, array{product_variant_id?: string|null, quantity?: int|string|null}>  $items
     * @return array<int, array{variant_id: string, requested_quantity: int, stock_quantity: int, booked_quantity: int, available_quantity: int, is_available: bool, variant_name: string, product_name: string, first_item_index: int}>
     */
    public function checkItems(array $items, CarbonInterface|string $pickupAt, CarbonInterface|string $returnDueAt, ?string $ignoreRentalId = null, ?string $ignoreRentalItemId = null): array
    {
        $pickupAt = $this->dateTime($pickupAt);
        $returnDueAt = $this->dateTime($returnDueAt);

        $requestedByVariant = [];
        $firstIndexByVariant = [];

        foreach ($items as $index => $item) {
            if (blank($item['product_variant_id'] ?? null)) {
                continue;
            }

            $variantId = (string) $item['product_variant_id'];
            $requestedByVariant[$variantId] = ($requestedByVariant[$variantId] ?? 0) + (int) ($item['quantity'] ?? 0);
            $firstIndexByVariant[$variantId] ??= $index;
        }

        if ($requestedByVariant === []) {
            return [];
        }

        $variants = ProductVariant::query()
            ->with('product:id,name')
            ->whereKey(array_keys($requestedByVariant))
            ->get()
            ->keyBy('id');

        return collect($requestedByVariant)
            ->map(function (int $requestedQuantity, string $variantId) use ($firstIndexByVariant, $ignoreRentalId, $ignoreRentalItemId, $pickupAt, $returnDueAt, $variants): array {
                /** @var ProductVariant $variant */
                $variant = $variants->get($variantId);
                $bookedQuantity = $this->bookedQuantity($variantId, $pickupAt, $returnDueAt, $ignoreRentalId, $ignoreRentalItemId);
                $availableQuantity = max(0, $variant->stock_quantity - $bookedQuantity);

                return [
                    'variant_id' => $variantId,
                    'requested_quantity' => $requestedQuantity,
                    'stock_quantity' => $variant->stock_quantity,
                    'booked_quantity' => $bookedQuantity,
                    'available_quantity' => $availableQuantity,
                    'is_available' => $availableQuantity >= $requestedQuantity,
                    'variant_name' => $variant->name,
                    'product_name' => $variant->product->name,
                    'first_item_index' => $firstIndexByVariant[$variantId],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{product_variant_id?: string|null, quantity?: int|string|null}>  $items
     * @return array<int, array{variant_id: string, requested_quantity: int, stock_quantity: int, booked_quantity: int, available_quantity: int, is_available: bool, variant_name: string, product_name: string, first_item_index: int}>
     */
    public function unavailableItems(array $items, CarbonInterface|string $pickupAt, CarbonInterface|string $returnDueAt, ?string $ignoreRentalId = null, ?string $ignoreRentalItemId = null): array
    {
        return collect($this->checkItems($items, $pickupAt, $returnDueAt, $ignoreRentalId, $ignoreRentalItemId))
            ->reject(fn (array $availability): bool => $availability['is_available'])
            ->values()
            ->all();
    }

    /**
     * @return array{variant_id: string, stock_quantity: int, booked_quantity: int, available_quantity: int, variant_name: string, product_name: string}
     */
    public function availabilityForVariant(ProductVariant $variant, CarbonInterface|string $pickupAt, CarbonInterface|string $returnDueAt, ?string $ignoreRentalId = null, ?string $ignoreRentalItemId = null): array
    {
        $pickupAt = $this->dateTime($pickupAt);
        $returnDueAt = $this->dateTime($returnDueAt);
        $variant->loadMissing('product:id,name');

        $bookedQuantity = $this->bookedQuantity($variant->id, $pickupAt, $returnDueAt, $ignoreRentalId, $ignoreRentalItemId);

        return [
            'variant_id' => $variant->id,
            'stock_quantity' => $variant->stock_quantity,
            'booked_quantity' => $bookedQuantity,
            'available_quantity' => max(0, $variant->stock_quantity - $bookedQuantity),
            'variant_name' => $variant->name,
            'product_name' => $variant->product->name,
        ];
    }

    public function bookedQuantity(string $variantId, CarbonInterface|string $pickupAt, CarbonInterface|string $returnDueAt, ?string $ignoreRentalId = null, ?string $ignoreRentalItemId = null): int
    {
        $pickupAt = $this->dateTime($pickupAt);
        $returnDueAt = $this->dateTime($returnDueAt);

        return (int) RentalItem::query()
            ->where('product_variant_id', $variantId)
            ->when($ignoreRentalItemId !== null, fn ($query) => $query->whereKeyNot($ignoreRentalItemId))
            ->whereHas('rental', function ($query) use ($pickupAt, $returnDueAt, $ignoreRentalId): void {
                $query->whereIn('status', self::BLOCKING_STATUSES)
                    ->where('pickup_at', '<', $returnDueAt)
                    ->where('return_due_at', '>', $pickupAt)
                    ->when($ignoreRentalId !== null, fn ($query) => $query->whereKeyNot($ignoreRentalId));
            })
            ->sum('quantity');
    }

    private function dateTime(CarbonInterface|string $value): CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        return Carbon::parse($value);
    }
}
