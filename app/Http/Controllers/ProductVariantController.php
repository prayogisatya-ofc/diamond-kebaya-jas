<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductVariantRequest;
use App\Http\Requests\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductVariantController extends Controller
{
    public function create(Product $product): Response
    {
        return Inertia::render('ProductVariants/Create', [
            'product' => $this->productPayload($product),
        ]);
    }

    public function store(StoreProductVariantRequest $request, Product $product): RedirectResponse
    {
        $product->variants()->create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('products.show', $product)->with('success', 'Varian produk berhasil ditambahkan.');
    }

    public function edit(ProductVariant $productVariant): Response
    {
        return Inertia::render('ProductVariants/Edit', [
            'product' => $this->productPayload($productVariant->product),
            'variant' => $this->variantPayload($productVariant),
        ]);
    }

    public function update(UpdateProductVariantRequest $request, ProductVariant $productVariant): RedirectResponse
    {
        $productVariant->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('products.show', $productVariant->product)->with('success', 'Varian produk berhasil diperbarui.');
    }

    public function destroy(ProductVariant $productVariant): RedirectResponse
    {
        $product = $productVariant->product;

        if ($productVariant->rentalItems()->exists() || $productVariant->rentalPackageItems()->exists()) {
            $productVariant->update(['is_active' => false]);

            return redirect()
                ->route('products.show', $product)
                ->with('warning', 'Varian sudah dipakai di rental atau paket, jadi tidak bisa dihapus. Varian sudah dinonaktifkan agar histori transaksi tetap aman.');
        }

        $productVariant->delete();

        return redirect()->route('products.show', $product)->with('success', 'Varian produk berhasil dihapus.');
    }

    /**
     * @return array{id: int, name: string, code: string|null}
     */
    private function productPayload(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
        ];
    }

    /**
     * @return array{id: int, product_id: int, sku: string|null, name: string, size: string|null, color: string|null, stock_quantity: int, rental_price: string|null, is_active: bool}
     */
    private function variantPayload(ProductVariant $variant): array
    {
        return [
            'id' => $variant->id,
            'product_id' => $variant->product_id,
            'sku' => $variant->sku,
            'name' => $variant->name,
            'size' => $variant->size,
            'color' => $variant->color,
            'stock_quantity' => $variant->stock_quantity,
            'rental_price' => $variant->rental_price,
            'is_active' => $variant->is_active,
        ];
    }
}
