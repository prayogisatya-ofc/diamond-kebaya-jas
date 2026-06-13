<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductCategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('ProductCategories/Index', [
            'categories' => ProductCategory::query()
                ->withCount('products')
                ->orderBy('name')
                ->get()
                ->map(fn (ProductCategory $category): array => $this->categoryPayload($category)),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('ProductCategories/Create');
    }

    public function store(StoreProductCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        ProductCategory::query()->create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['name']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('product-categories.index')->with('success', 'Kategori produk berhasil ditambahkan.');
    }

    public function show(ProductCategory $productCategory): RedirectResponse
    {
        return redirect()->route('products.index', [
            'category' => $productCategory->id,
        ]);
    }

    public function edit(ProductCategory $productCategory): Response
    {
        return Inertia::render('ProductCategories/Edit', [
            'category' => $this->categoryPayload($productCategory->loadCount('products')),
        ]);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): RedirectResponse
    {
        $validated = $request->validated();

        $productCategory->update([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['name'], $productCategory),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('product-categories.index')->with('success', 'Kategori produk berhasil diperbarui.');
    }

    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        if ($productCategory->products()->exists()) {
            return redirect()
                ->route('product-categories.index')
                ->withErrors([
                    'category' => 'Kategori tidak dapat dihapus karena masih digunakan produk.',
                ]);
        }

        $productCategory->delete();

        return redirect()->route('product-categories.index')->with('success', 'Kategori produk berhasil dihapus.');
    }

    /**
     * @return array{id: int, name: string, slug: string, is_active: bool, products_count: int}
     */
    private function categoryPayload(ProductCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'is_active' => $category->is_active,
            'products_count' => (int) ($category->products_count ?? 0),
        ];
    }

    private function uniqueSlug(string $name, ?ProductCategory $ignoredCategory = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 2;

        while (ProductCategory::query()
            ->where('slug', $slug)
            ->when($ignoredCategory, fn ($query) => $query->whereKeyNot($ignoredCategory->id))
            ->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
