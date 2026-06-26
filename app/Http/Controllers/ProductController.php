<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Support\UploadedImageCompressor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private readonly UploadedImageCompressor $imageCompressor) {}

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'category' => $request->string('category')->trim()->toString() ?: null,
        ];

        $products = Product::query()
            ->with('category:id,name')
            ->withCount('variants')
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $query->where(function ($query) use ($filters): void {
                    $query
                        ->where('name', 'like', "%{$filters['search']}%")
                        ->orWhere('code', 'like', "%{$filters['search']}%");
                });
            })
            ->when($filters['category'], fn ($query, string $categoryId) => $query->where('product_category_id', $categoryId))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Product $product): array => $this->productPayload($product));

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => $filters,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Products/Create', [
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = DB::transaction(function () use ($request): Product {
            $validated = $this->productData($request->validated());

            if ($request->hasFile('image')) {
                $validated['image_path'] = $this->imageCompressor->store($request->file('image'), 'products');
            }

            return Product::query()->create([
                ...$validated,
                'is_active' => $request->boolean('is_active'),
            ]);
        });

        return redirect()->route('products.show', $product)->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product): Response
    {
        $product->load('category:id,name', 'variants');

        return Inertia::render('Products/Show', [
            'product' => $this->productPayload($product),
            'variants' => $product->variants
                ->sortByDesc('created_at')
                ->values()
                ->map(fn (ProductVariant $variant): array => $this->variantPayload($variant)),
        ]);
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Edit', [
            'product' => $this->productPayload($product->load('category:id,name')),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $imagePathToDelete = DB::transaction(function () use ($request, $product): ?string {
            $validated = $this->productData($request->validated());
            $imagePathToDelete = null;

            if ($request->hasFile('image')) {
                $imagePathToDelete = $product->image_path;
                $validated['image_path'] = $this->imageCompressor->store($request->file('image'), 'products');
            } elseif ($request->boolean('remove_image') && $product->image_path) {
                $imagePathToDelete = $product->image_path;
                $validated['image_path'] = null;
            }

            $product->update([
                ...$validated,
                'is_active' => $request->boolean('is_active'),
            ]);

            return $imagePathToDelete;
        });

        if ($imagePathToDelete) {
            Storage::disk('public')->delete($imagePathToDelete);
        }

        return redirect()->route('products.show', $product)->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->rentalItems()->exists() || $product->rentalPackageItems()->exists()) {
            $product->update(['is_active' => false]);

            return redirect()
                ->route('products.index')
                ->with('warning', 'Produk sudah dipakai di rental atau paket, jadi tidak bisa dihapus. Produk sudah dinonaktifkan agar histori transaksi tetap aman.');
        }

        $imagePaths = $product->variants()
            ->whereNotNull('image_path')
            ->pluck('image_path')
            ->prepend($product->image_path)
            ->filter()
            ->values();

        $product->delete();

        if ($imagePaths->isNotEmpty()) {
            Storage::disk('public')->delete($imagePaths->all());
        }

        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }

    /**
     * @return array<int, array{id: int, name: string, is_active: bool}>
     */
    private function categoryOptions(): array
    {
        return ProductCategory::query()
            ->orderBy('name')
            ->get(['id', 'name', 'is_active'])
            ->map(fn (ProductCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'is_active' => $category->is_active,
            ])
            ->all();
    }

    /**
     * @return array{id: int, product_category_id: int, category: array{id: int, name: string}|null, name: string, code: string|null, description: string|null, image_path: string|null, image_url: string|null, base_rental_price: string, is_active: bool, variants_count: int}
     */
    private function productPayload(Product $product): array
    {
        return [
            'id' => $product->id,
            'product_category_id' => $product->product_category_id,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
            ] : null,
            'name' => $product->name,
            'code' => $product->code,
            'description' => $product->description,
            'image_path' => $product->image_path,
            'image_url' => $product->imageUrl(),
            'base_rental_price' => $product->base_rental_price,
            'is_active' => $product->is_active,
            'variants_count' => (int) ($product->variants_count ?? $product->variants()->count()),
        ];
    }

    /**
     * @return array{id: int, product_id: int, sku: string|null, name: string, size: string|null, color: string|null, image_path: string|null, image_url: string|null, stock_quantity: int, rental_price: string|null, is_active: bool}
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
            'image_path' => $variant->image_path,
            'image_url' => $variant->imageUrl(),
            'stock_quantity' => $variant->stock_quantity,
            'rental_price' => $variant->rental_price,
            'is_active' => $variant->is_active,
        ];
    }

    /**
     * @param  array{product_category_id?: int|null, new_product_category_name?: string|null, name: string, code?: string|null, description?: string|null, image?: mixed, remove_image?: bool, base_rental_price: numeric-string|float|int, is_active?: bool}  $validated
     * @return array{product_category_id: int, name: string, code?: string|null, description?: string|null, base_rental_price: numeric-string|float|int, is_active?: bool}
     */
    private function productData(array $validated): array
    {
        $newCategoryName = trim((string) ($validated['new_product_category_name'] ?? ''));
        unset($validated['new_product_category_name']);
        unset($validated['image'], $validated['remove_image']);

        if ($newCategoryName !== '') {
            $validated['product_category_id'] = ProductCategory::query()->create([
                'name' => $newCategoryName,
                'slug' => $this->uniqueCategorySlug($newCategoryName),
                'is_active' => true,
            ])->id;
        }

        return $validated;
    }

    private function uniqueCategorySlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 2;

        while (ProductCategory::query()
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
