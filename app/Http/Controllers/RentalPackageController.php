<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRentalPackageRequest;
use App\Http\Requests\UpdateRentalPackageRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RentalPackage;
use App\Models\RentalPackageItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RentalPackageController extends Controller
{
    public function index(): Response
    {
        $packages = RentalPackage::query()
            ->withCount('items')
            ->orderBy('name')
            ->get()
            ->map(fn (RentalPackage $rentalPackage): array => $this->packagePayload($rentalPackage));

        return Inertia::render('RentalPackages/Index', [
            'packages' => $packages,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('RentalPackages/Create', [
            'products' => $this->productOptions(),
        ]);
    }

    public function store(StoreRentalPackageRequest $request): RedirectResponse
    {
        $rentalPackage = DB::transaction(function () use ($request): RentalPackage {
            $validated = $request->validated();

            $rentalPackage = RentalPackage::query()->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'package_price' => $validated['package_price'],
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncItems($rentalPackage, $validated['items']);

            return $rentalPackage;
        });

        return redirect()->route('rental-packages.show', $rentalPackage)->with('success', 'Paket rental berhasil ditambahkan.');
    }

    public function show(RentalPackage $rentalPackage): Response
    {
        $rentalPackage->load([
            'items.product:id,name,code,base_rental_price',
            'items.productVariant:id,product_id,name,sku,size,color,rental_price',
        ]);

        return Inertia::render('RentalPackages/Show', [
            'rentalPackage' => $this->packagePayload($rentalPackage),
            'items' => $rentalPackage->items
                ->sortBy('id')
                ->values()
                ->map(fn (RentalPackageItem $item): array => $this->itemPayload($item)),
        ]);
    }

    public function edit(RentalPackage $rentalPackage): Response
    {
        $rentalPackage->load([
            'items.product:id,name,code,base_rental_price',
            'items.productVariant:id,product_id,name,sku,size,color,rental_price',
        ]);

        return Inertia::render('RentalPackages/Edit', [
            'rentalPackage' => $this->packagePayload($rentalPackage),
            'items' => $rentalPackage->items
                ->sortBy('id')
                ->values()
                ->map(fn (RentalPackageItem $item): array => $this->itemPayload($item)),
            'products' => $this->productOptions(),
        ]);
    }

    public function update(UpdateRentalPackageRequest $request, RentalPackage $rentalPackage): RedirectResponse
    {
        DB::transaction(function () use ($request, $rentalPackage): void {
            $validated = $request->validated();

            $rentalPackage->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'package_price' => $validated['package_price'],
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncItems($rentalPackage, $validated['items']);
        });

        return redirect()->route('rental-packages.show', $rentalPackage)->with('success', 'Paket rental berhasil diperbarui.');
    }

    public function destroy(RentalPackage $rentalPackage): RedirectResponse
    {
        $rentalPackage->delete();

        return redirect()->route('rental-packages.index')->with('success', 'Paket rental berhasil dihapus.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(RentalPackage $rentalPackage, array $items): void
    {
        $keptItemIds = collect($items)
            ->pluck('id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->all();

        $rentalPackage->items()
            ->when($keptItemIds !== [], fn ($query) => $query->whereKeyNot($keptItemIds))
            ->delete();

        foreach ($items as $item) {
            $attributes = [
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['product_variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'default_item_price' => $item['default_item_price'] ?? null,
                'is_optional' => (bool) ($item['is_optional'] ?? false),
                'notes' => $item['notes'] ?? null,
            ];

            if (filled($item['id'] ?? null)) {
                $rentalPackage->items()->whereKey($item['id'])->update($attributes);

                continue;
            }

            $rentalPackage->items()->create($attributes);
        }
    }

    /**
     * @return array<int, array{id: int, name: string, code: string|null, image_url: string|null, base_rental_price: string, variants: array<int, array{id: int, name: string, sku: string|null, size: string|null, color: string|null, rental_price: string|null}>}>
     */
    private function productOptions(): array
    {
        return Product::query()
            ->with('variants:id,product_id,name,sku,size,color,rental_price')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'image_path', 'base_rental_price'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'image_url' => $product->imageUrl(),
                'base_rental_price' => $product->base_rental_price,
                'variants' => $product->variants
                    ->sortBy('name')
                    ->values()
                    ->map(fn (ProductVariant $variant): array => [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'sku' => $variant->sku,
                        'size' => $variant->size,
                        'color' => $variant->color,
                        'rental_price' => $variant->rental_price,
                    ])
                    ->all(),
            ])
            ->all();
    }

    /**
     * @return array{id: int, name: string, description: string|null, package_price: string, is_active: bool, items_count: int}
     */
    private function packagePayload(RentalPackage $rentalPackage): array
    {
        return [
            'id' => $rentalPackage->id,
            'name' => $rentalPackage->name,
            'description' => $rentalPackage->description,
            'package_price' => $rentalPackage->package_price,
            'is_active' => $rentalPackage->is_active,
            'items_count' => (int) ($rentalPackage->items_count ?? $rentalPackage->items()->count()),
        ];
    }

    /**
     * @return array{id: int, product_id: int, product_variant_id: int|null, quantity: int, default_item_price: string|null, is_optional: bool, notes: string|null, product: array{id: int, name: string, code: string|null, base_rental_price: string}|null, product_variant: array{id: int, name: string, sku: string|null, size: string|null, color: string|null, rental_price: string|null}|null}
     */
    private function itemPayload(RentalPackageItem $item): array
    {
        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'quantity' => $item->quantity,
            'default_item_price' => $item->default_item_price,
            'is_optional' => $item->is_optional,
            'notes' => $item->notes,
            'product' => $item->product ? [
                'id' => $item->product->id,
                'name' => $item->product->name,
                'code' => $item->product->code,
                'base_rental_price' => $item->product->base_rental_price,
            ] : null,
            'product_variant' => $item->productVariant ? [
                'id' => $item->productVariant->id,
                'name' => $item->productVariant->name,
                'sku' => $item->productVariant->sku,
                'size' => $item->productVariant->size,
                'color' => $item->productVariant->color,
                'rental_price' => $item->productVariant->rental_price,
            ] : null,
        ];
    }
}
