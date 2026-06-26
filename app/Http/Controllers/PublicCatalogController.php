<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\RentalPackage;
use App\Models\RentalPackageItem;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PublicCatalogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'category' => $request->string('category')->trim()->toString(),
            'sort' => $request->string('sort')->trim()->toString(),
        ];

        $productsQuery = Product::query()
            ->with([
                'category:id,name,slug,is_active',
                'variants' => fn ($query) => $query
                    ->where('is_active', true)
                    ->latest()
                    ->select(['id', 'product_id', 'sku', 'name', 'size', 'color', 'image_path', 'stock_quantity', 'rental_price', 'is_active']),
            ])
            ->where('is_active', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $query->where(function ($query) use ($filters): void {
                    $query
                        ->where('name', 'like', "%{$filters['search']}%")
                        ->orWhere('code', 'like', "%{$filters['search']}%")
                        ->orWhereHas('variants', function ($query) use ($filters): void {
                            $query
                                ->where('name', 'like', "%{$filters['search']}%")
                                ->orWhere('sku', 'like', "%{$filters['search']}%")
                                ->orWhere('color', 'like', "%{$filters['search']}%");
                        });
                });
            })
            ->when($filters['category'] !== '', fn ($query) => $query->where('product_category_id', $filters['category']));

        $this->applySort($productsQuery, $filters['sort']);

        $products = Inertia::scroll(
            $productsQuery
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Product $product): array => $this->productPayload($product, 6))
        );

        return Inertia::render('Public/Catalog', [
            'catalogStore' => $this->storePayload(),
            'products' => $products,
            'packages' => $this->packageOptions($filters),
            'filters' => $filters,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function packageShow(RentalPackage $rentalPackage): Response
    {
        abort_unless($rentalPackage->is_active, 404);

        $rentalPackage->load([
            'items.product:id,product_category_id,name,code,image_path,base_rental_price,is_active',
            'items.product.category:id,name,slug,is_active',
            'items.productVariant:id,product_id,name,sku,size,color,image_path,rental_price,is_active',
        ]);

        $items = $rentalPackage->items
            ->filter(fn (RentalPackageItem $item): bool => (bool) $item->product?->is_active && (bool) $item->product?->category?->is_active)
            ->sortByDesc('created_at')
            ->values();

        $relatedPackages = RentalPackage::query()
            ->with([
                'items' => fn ($query) => $query->latest(),
                'items.product:id,product_category_id,name,code,image_path,base_rental_price,is_active',
                'items.product.category:id,name,slug,is_active',
                'items.productVariant:id,product_id,name,sku,size,color,image_path,rental_price,is_active',
            ])
            ->withCount('items')
            ->where('is_active', true)
            ->whereKeyNot($rentalPackage->id)
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn (RentalPackage $relatedPackage): array => $this->packagePayload($relatedPackage))
            ->values()
            ->all();

        return Inertia::render('Public/PackageShow', [
            'catalogStore' => $this->storePayload(),
            'rentalPackage' => $this->packagePayload($rentalPackage, $items),
            'items' => $items
                ->map(fn (RentalPackageItem $item): array => $this->packageItemPayload($item))
                ->all(),
            'relatedPackages' => $relatedPackages,
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category:id,name,slug,is_active',
            'variants' => fn ($query) => $query
                ->where('is_active', true)
                ->latest()
                ->select(['id', 'product_id', 'sku', 'name', 'size', 'color', 'image_path', 'stock_quantity', 'rental_price', 'is_active']),
        ]);

        abort_unless($product->category?->is_active, 404);

        $relatedProducts = Product::query()
            ->with([
                'category:id,name,slug,is_active',
                'variants' => fn ($query) => $query
                    ->where('is_active', true)
                    ->latest()
                    ->select(['id', 'product_id', 'sku', 'name', 'size', 'color', 'image_path', 'stock_quantity', 'rental_price', 'is_active']),
            ])
            ->where('is_active', true)
            ->where('product_category_id', $product->product_category_id)
            ->whereKeyNot($product->id)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (Product $relatedProduct): array => $this->productPayload($relatedProduct, 4))
            ->values()
            ->all();

        return Inertia::render('Public/ProductShow', [
            'catalogStore' => $this->storePayload(),
            'product' => $this->productPayload($product),
            'relatedProducts' => $relatedProducts,
        ]);
    }

    public function howToRent(): Response
    {
        return Inertia::render('Public/HowToRent', [
            'catalogStore' => $this->storePayload(),
            'steps' => $this->howToRentSteps(),
            'tips' => $this->howToRentTips(),
        ]);
    }

    public function faq(): Response
    {
        return Inertia::render('Public/Faq', [
            'catalogStore' => $this->storePayload(),
            'faqGroups' => $this->faqGroups(),
        ]);
    }

    /**
     * @return array<int, array{id: string, name: string, slug: string}>
     */
    private function categoryOptions(): array
    {
        return ProductCategory::query()
            ->where('is_active', true)
            ->whereHas('products', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (ProductCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])
            ->all();
    }

    /**
     * @return array{name: string, address: string, whatsapp_number: string, logo_url: string|null, primary_color: string, footer_note: string}
     */
    private function storePayload(): array
    {
        $profile = Setting::storeProfile();

        return [
            'name' => $profile['store_name'],
            'address' => $profile['store_address'],
            'whatsapp_number' => $profile['store_whatsapp_number'],
            'logo_url' => $profile['store_logo_url'],
            'primary_color' => $profile['primary_color'],
            'footer_note' => $profile['invoice_footer_note'],
        ];
    }

    private function applySort(Builder $query, string $sort): void
    {
        $query->withMin([
            'variants as lowest_variant_price' => fn ($variantQuery) => $variantQuery
                ->where('is_active', true)
                ->whereNotNull('rental_price'),
        ], 'rental_price');

        match ($sort) {
            'price_asc' => $query
                ->orderByRaw('COALESCE(lowest_variant_price, base_rental_price) asc')
                ->orderBy('name'),
            'price_desc' => $query
                ->orderByRaw('COALESCE(lowest_variant_price, base_rental_price) desc')
                ->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'latest' => $query->latest(),
            default => $query->latest(),
        };
    }

    /**
     * @param  array{search: string, category: string, sort: string}  $filters
     * @return array<int, array{id: string, name: string, description: string|null, package_price: string, image_url: string|null, items_count: int, required_items_count: int, optional_items_count: int, preview_items: array<int, array{id: string, name: string, variant_name: string|null, image_url: string|null, quantity: int, is_optional: bool}>}>
     */
    private function packageOptions(array $filters): array
    {
        $query = RentalPackage::query()
            ->with([
                'items' => fn ($query) => $query->latest(),
                'items.product:id,product_category_id,name,code,image_path,base_rental_price,is_active',
                'items.product.category:id,name,slug,is_active',
                'items.productVariant:id,product_id,name,sku,size,color,image_path,rental_price,is_active',
            ])
            ->withCount('items')
            ->where('is_active', true)
            ->whereHas('items.product', fn ($query) => $query
                ->where('is_active', true)
                ->whereHas('category', fn ($query) => $query->where('is_active', true)))
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $query->where(function ($query) use ($filters): void {
                    $query
                        ->where('name', 'like', "%{$filters['search']}%")
                        ->orWhere('description', 'like', "%{$filters['search']}%")
                        ->orWhereHas('items.product', function ($query) use ($filters): void {
                            $query
                                ->where('name', 'like', "%{$filters['search']}%")
                                ->orWhere('code', 'like', "%{$filters['search']}%");
                        })
                        ->orWhereHas('items.productVariant', function ($query) use ($filters): void {
                            $query
                                ->where('name', 'like', "%{$filters['search']}%")
                                ->orWhere('sku', 'like', "%{$filters['search']}%")
                                ->orWhere('color', 'like', "%{$filters['search']}%");
                        });
                });
            })
            ->when($filters['category'] !== '', function ($query) use ($filters): void {
                $query->whereHas('items.product', fn ($query) => $query
                    ->where('product_category_id', $filters['category'])
                    ->where('is_active', true)
                    ->whereHas('category', fn ($query) => $query->where('is_active', true)));
            });

        match ($filters['sort']) {
            'price_asc' => $query->orderBy('package_price')->orderBy('name'),
            'price_desc' => $query->orderByDesc('package_price')->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'name_asc' => $query->orderBy('name'),
            default => $query->latest(),
        };

        return $query
            ->limit(6)
            ->get()
            ->map(fn (RentalPackage $rentalPackage): array => $this->packagePayload($rentalPackage))
            ->all();
    }

    /**
     * @return array<int, array{title: string, description: string}>
     */
    private function howToRentSteps(): array
    {
        return [
            [
                'title' => 'Pilih koleksi untuk referensi',
                'description' => 'Cari produk dari katalog dan simpan item yang Anda minati sebagai referensi awal sebelum datang ke toko.',
            ],
            [
                'title' => 'Buat janji datang ke toko',
                'description' => 'Hubungi admin untuk mengatur waktu kunjungan. Transaksi rental tidak langsung difinalkan dari chat karena customer perlu mencoba pakaian terlebih dahulu.',
            ],
            [
                'title' => 'Fitting dan pilih item yang paling pas',
                'description' => 'Saat datang ke toko, customer mencoba pakaian, memilih ukuran atau varian yang cocok, lalu staff membantu menyesuaikan kebutuhan final.',
            ],
            [
                'title' => 'Fiksasi pesanan setelah cocok',
                'description' => 'Setelah hasil fitting sesuai, barulah item, harga, dan detail pesanan difinalkan sebagai transaksi rental yang resmi.',
            ],
            [
                'title' => 'Atur jadwal pengambilan',
                'description' => 'Setelah transaksi fix, staff akan mencatat jadwal ambil dan jadwal kembali sesuai kesepakatan rental.',
            ],
            [
                'title' => 'Ambil dan kembalikan sesuai jadwal',
                'description' => 'Barang diambil pada jadwal yang sudah ditentukan. Saat pengambilan, pelunasan dan jaminan diselesaikan jika belum lengkap, lalu barang dikembalikan tepat waktu.',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function howToRentTips(): array
    {
        return [
            'Katalog berfungsi sebagai referensi awal, tetapi keputusan final tetap dilakukan setelah fitting langsung di toko.',
            'Jika membawa beberapa referensi model, kirim dulu ke admin agar staff bisa menyiapkan opsi yang paling relevan saat Anda datang.',
            'Jadwal pengambilan baru dicatat setelah hasil fitting sudah cocok dan pesanan benar-benar difinalkan.',
        ];
    }

    /**
     * @return array<int, array{title: string, items: array<int, array{question: string, answer: string}>}>
     */
    private function faqGroups(): array
    {
        return [
            [
                'title' => 'Booking & Ketersediaan',
                'items' => [
                    [
                        'question' => 'Apakah bisa langsung booking hanya dari WhatsApp?',
                        'answer' => 'Tidak untuk finalisasi rental. WhatsApp digunakan untuk konsultasi awal dan membuat janji datang, tetapi customer tetap perlu datang fitting sebelum transaksi dipastikan.',
                    ],
                    [
                        'question' => 'Apakah semua produk di katalog pasti tersedia?',
                        'answer' => 'Tidak selalu. Ketersediaan final tetap dicek berdasarkan stok dan tanggal rental karena item bisa sedang dibooking customer lain.',
                    ],
                    [
                        'question' => 'Kapan transaksi dianggap fix?',
                        'answer' => 'Transaksi dianggap fix setelah customer datang fitting, item yang dipilih cocok, lalu staff dan customer menyepakati detail pesanan secara final.',
                    ],
                ],
            ],
            [
                'title' => 'Pengambilan & Pengembalian',
                'items' => [
                    [
                        'question' => 'Kapan jadwal pengambilan ditentukan?',
                        'answer' => 'Jadwal pengambilan diatur setelah fitting selesai dan pesanan sudah difinalkan. Jadi bukan ditentukan saat masih tahap konsultasi awal.',
                    ],
                    [
                        'question' => 'Kapan jaminan diserahkan?',
                        'answer' => 'Jaminan KTP atau SIM diserahkan saat pengambilan barang, bukan saat awal konsultasi atau saat fitting.',
                    ],
                    [
                        'question' => 'Apakah barang harus lunas saat diambil?',
                        'answer' => 'Ya. Jika masih ada sisa pembayaran, pelunasan dilakukan saat proses pengambilan sebelum transaksi ditandai sudah diambil.',
                    ],
                ],
            ],
            [
                'title' => 'Perubahan Pesanan',
                'items' => [
                    [
                        'question' => 'Bisa ganti varian setelah fitting awal?',
                        'answer' => 'Bisa selama stok dan jadwal masih memungkinkan. Namun perubahan tetap perlu dikonfirmasi lagi oleh staff sebelum transaksi final dicatat.',
                    ],
                    [
                        'question' => 'Bagaimana jika telat mengembalikan?',
                        'answer' => 'Keterlambatan dihitung berdasarkan tanggal pengembalian. Denda manual akan diinformasikan dan dicatat saat return diproses oleh staff.',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{type: string, id: string, name: string, code: string|null, description: string|null, category: array{id: string, name: string}|null, image_url: string|null, base_rental_price: string, lowest_price: float|int|string, variants_count: int, total_stock: int, colors: array<int, string>, sizes: array<int, string>, variants: array<int, array{id: string, sku: string|null, name: string, size: string|null, color: string|null, image_url: string|null, stock_quantity: int, rental_price: string|null}>}
     */
    private function productPayload(Product $product, ?int $variantLimit = null): array
    {
        $variants = $product->variants;
        $visibleVariants = $variantLimit ? $variants->take($variantLimit) : $variants;
        $prices = $variants
            ->pluck('rental_price')
            ->filter(fn (mixed $price): bool => filled($price))
            ->push($product->base_rental_price)
            ->map(fn (mixed $price): float => (float) $price);

        return [
            'type' => 'product',
            'id' => $product->id,
            'name' => $product->name,
            'created_at' => $product->created_at,
            'code' => $product->code,
            'description' => $product->description,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
            ] : null,
            'image_url' => $product->imageUrl(),
            'base_rental_price' => $product->base_rental_price,
            'lowest_price' => $prices->min() ?? $product->base_rental_price,
            'variants_count' => $variants->count(),
            'total_stock' => $variants->sum('stock_quantity'),
            'colors' => $variants->pluck('color')->filter()->unique()->values()->all(),
            'sizes' => $variants->pluck('size')->filter()->unique()->values()->all(),
            'variants' => $visibleVariants
                ->map(fn (ProductVariant $variant): array => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'name' => $variant->name,
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'image_url' => $variant->imageUrl(),
                    'stock_quantity' => $variant->stock_quantity,
                    'rental_price' => $variant->rental_price,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, RentalPackageItem>|null  $items
     * @return array{type: string, id: string, name: string, description: string|null, package_price: string, image_url: string|null, items_count: int, required_items_count: int, optional_items_count: int, preview_items: array<int, array{id: string, name: string, variant_name: string|null, image_url: string|null, quantity: int, is_optional: bool}>}
     */
    private function packagePayload(RentalPackage $rentalPackage, ?Collection $items = null): array
    {
        $packageItems = $items ?? $rentalPackage->items
            ->filter(fn (RentalPackageItem $item): bool => (bool) $item->product?->is_active && (bool) $item->product?->category?->is_active)
            ->values();

        return [
            'type' => 'package',
            'id' => $rentalPackage->id,
            'name' => $rentalPackage->name,
            'created_at' => $rentalPackage->created_at,
            'description' => $rentalPackage->description,
            'package_price' => $rentalPackage->package_price,
            'image_url' => $this->packageImageUrl($packageItems),
            'items_count' => $packageItems->count(),
            'required_items_count' => $packageItems->where('is_optional', false)->count(),
            'optional_items_count' => $packageItems->where('is_optional', true)->count(),
            'preview_items' => $packageItems
                ->take(4)
                ->map(fn (RentalPackageItem $item): array => $this->packageItemPreviewPayload($item))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{id: string, name: string, variant_name: string|null, image_url: string|null, quantity: int, default_item_price: string|null, is_optional: bool, notes: string|null, product: array{id: string, name: string, code: string|null, image_url: string|null, base_rental_price: string, category: array{id: string, name: string}|null}|null, product_variant: array{id: string, name: string, sku: string|null, size: string|null, color: string|null, image_url: string|null, rental_price: string|null}|null}
     */
    private function packageItemPayload(RentalPackageItem $item): array
    {
        return [
            ...$this->packageItemPreviewPayload($item),
            'default_item_price' => $item->default_item_price,
            'notes' => $item->notes,
            'product' => $item->product ? [
                'id' => $item->product->id,
                'name' => $item->product->name,
                'code' => $item->product->code,
                'image_url' => $item->product->imageUrl(),
                'base_rental_price' => $item->product->base_rental_price,
                'category' => $item->product->category ? [
                    'id' => $item->product->category->id,
                    'name' => $item->product->category->name,
                ] : null,
            ] : null,
            'product_variant' => $item->productVariant ? [
                'id' => $item->productVariant->id,
                'name' => $item->productVariant->name,
                'sku' => $item->productVariant->sku,
                'size' => $item->productVariant->size,
                'color' => $item->productVariant->color,
                'image_url' => $item->productVariant->imageUrl(),
                'rental_price' => $item->productVariant->rental_price,
            ] : null,
        ];
    }

    /**
     * @return array{id: string, name: string, variant_name: string|null, image_url: string|null, quantity: int, is_optional: bool}
     */
    private function packageItemPreviewPayload(RentalPackageItem $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->product?->name ?? 'Produk paket',
            'variant_name' => $item->productVariant?->name,
            'image_url' => $item->productVariant?->imageUrl() ?: $item->product?->imageUrl(),
            'quantity' => $item->quantity,
            'is_optional' => $item->is_optional,
        ];
    }

    /**
     * @param  Collection<int, RentalPackageItem>  $items
     */
    private function packageImageUrl(Collection $items): ?string
    {
        return $items
            ->map(fn (RentalPackageItem $item): ?string => $item->productVariant?->imageUrl() ?: $item->product?->imageUrl())
            ->first(fn (?string $imageUrl): bool => filled($imageUrl));
    }
}
