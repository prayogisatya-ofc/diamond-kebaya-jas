<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_can_view_public_catalog(): void
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Kebaya',
            'is_active' => true,
        ]);
        $product = Product::factory()->create([
            'product_category_id' => $category->id,
            'name' => 'Kebaya Merah Modern',
            'is_active' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size M Merah',
            'color' => 'Merah',
            'stock_quantity' => 2,
            'is_active' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/Catalog', false)
                ->where('products.data.0.name', 'Kebaya Merah Modern')
                ->where('products.data.0.variants.0.name', 'Size M Merah')
                ->where('categories.0.name', 'Kebaya')
                ->has('catalogStore.name')
            );
    }

    public function test_catalog_path_redirects_to_public_home(): void
    {
        $this->get('/catalog')
            ->assertRedirect('/');
    }

    public function test_public_catalog_only_shows_active_products_and_active_categories(): void
    {
        $activeCategory = ProductCategory::factory()->create([
            'name' => 'Jas',
            'is_active' => true,
        ]);
        $inactiveCategory = ProductCategory::factory()->create([
            'name' => 'Arsip',
            'is_active' => false,
        ]);

        Product::factory()->create([
            'product_category_id' => $activeCategory->id,
            'name' => 'Jas Hitam Aktif',
            'is_active' => true,
        ]);
        Product::factory()->create([
            'product_category_id' => $activeCategory->id,
            'name' => 'Jas Nonaktif',
            'is_active' => false,
        ]);
        Product::factory()->create([
            'product_category_id' => $inactiveCategory->id,
            'name' => 'Produk Kategori Nonaktif',
            'is_active' => true,
        ]);

        $this->get(route('public.catalog'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('products.data.0.name', 'Jas Hitam Aktif')
                ->where('products.total', 1)
                ->where('categories.0.name', 'Jas')
                ->has('categories', 1)
            );
    }

    public function test_public_catalog_supports_search_and_category_filter(): void
    {
        $kebaya = ProductCategory::factory()->create([
            'name' => 'Kebaya',
            'is_active' => true,
        ]);
        $jas = ProductCategory::factory()->create([
            'name' => 'Jas',
            'is_active' => true,
        ]);

        Product::factory()->create([
            'product_category_id' => $kebaya->id,
            'name' => 'Kebaya Silver',
            'is_active' => true,
        ]);
        Product::factory()->create([
            'product_category_id' => $jas->id,
            'name' => 'Jas Silver',
            'is_active' => true,
        ]);

        $this->get(route('public.catalog', [
            'search' => 'Silver',
            'category' => $kebaya->id,
        ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.search', 'Silver')
                ->where('filters.category', $kebaya->id)
                ->where('products.data.0.name', 'Kebaya Silver')
                ->where('products.total', 1)
            );
    }

    public function test_public_catalog_supports_sorting_by_price_descending(): void
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Kebaya',
            'is_active' => true,
        ]);

        $expensiveProduct = Product::factory()->create([
            'product_category_id' => $category->id,
            'name' => 'Kebaya Premium',
            'base_rental_price' => 450000,
            'is_active' => true,
        ]);

        $cheapProduct = Product::factory()->create([
            'product_category_id' => $category->id,
            'name' => 'Kebaya Basic',
            'base_rental_price' => 150000,
            'is_active' => true,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $expensiveProduct->id,
            'rental_price' => 500000,
            'is_active' => true,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $cheapProduct->id,
            'rental_price' => 125000,
            'is_active' => true,
        ]);

        $this->get(route('public.catalog', [
            'sort' => 'price_desc',
        ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.sort', 'price_desc')
                ->where('products.data.0.name', 'Kebaya Premium')
                ->where('products.data.1.name', 'Kebaya Basic')
            );
    }

    public function test_guest_can_view_public_product_detail(): void
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Kebaya',
            'is_active' => true,
        ]);
        $product = Product::factory()->create([
            'product_category_id' => $category->id,
            'name' => 'Kebaya Detail',
            'is_active' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size M',
            'is_active' => true,
        ]);

        $this->get(route('public.catalog.show', $product))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/ProductShow', false)
                ->where('product.name', 'Kebaya Detail')
                ->where('product.variants.0.name', 'Size M')
                ->where('product.category.name', 'Kebaya')
                ->has('catalogStore.name')
            );
    }

    public function test_guest_can_view_public_how_to_rent_page(): void
    {
        $this->get(route('public.how-to-rent'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/HowToRent', false)
                ->has('catalogStore.name')
                ->has('steps', 6)
                ->has('tips', 3)
            );
    }

    public function test_guest_can_view_public_faq_page(): void
    {
        $this->get(route('public.faq'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/Faq', false)
                ->has('catalogStore.name')
                ->has('faqGroups', 3)
            );
    }

    public function test_public_sitemap_includes_public_pages_and_active_products(): void
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Kebaya',
            'is_active' => true,
        ]);
        $activeProduct = Product::factory()->create([
            'product_category_id' => $category->id,
            'name' => 'Kebaya Sitemap',
            'is_active' => true,
        ]);
        $inactiveProduct = Product::factory()->create([
            'product_category_id' => $category->id,
            'name' => 'Kebaya Arsip',
            'is_active' => false,
        ]);

        $this->get(route('public.sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('public.catalog'), false)
            ->assertSee(route('public.how-to-rent'), false)
            ->assertSee(route('public.faq'), false)
            ->assertSee(route('public.catalog.show', $activeProduct), false)
            ->assertDontSee(route('public.catalog.show', $inactiveProduct), false);
    }

    public function test_public_robots_references_sitemap(): void
    {
        $this->get(route('public.robots'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('User-agent: *')
            ->assertSee('Allow: /')
            ->assertSee('Disallow: /panel')
            ->assertSee('Sitemap: '.route('public.sitemap'));
    }

    public function test_public_catalog_renders_server_side_og_meta_tags(): void
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Kebaya',
            'is_active' => true,
        ]);
        Product::factory()->create([
            'product_category_id' => $category->id,
            'name' => 'Kebaya Server Meta',
            'is_active' => true,
        ]);

        $this->get(route('public.catalog'))
            ->assertOk()
            ->assertSee('<meta property="og:title" content="Katalog Kebaya &amp; Jas | Diamond Kebaya &amp; Jas">', false)
            ->assertSee('<meta name="description"', false)
            ->assertSee('<link rel="canonical" href="'.route('public.catalog').'">', false);
    }

    public function test_public_product_detail_renders_server_side_og_meta_tags(): void
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Kebaya',
            'is_active' => true,
        ]);
        $product = Product::factory()->create([
            'product_category_id' => $category->id,
            'name' => 'Kebaya Preview Share',
            'description' => 'Produk untuk cek preview OG.',
            'is_active' => true,
        ]);

        $this->get(route('public.catalog.show', $product))
            ->assertOk()
            ->assertSee('<meta property="og:title" content="Kebaya Preview Share | Diamond Kebaya &amp; Jas">', false)
            ->assertSee('<meta property="og:description" content="Produk untuk cek preview OG.">', false)
            ->assertSee('<meta property="og:type" content="product">', false);
    }

    public function test_public_product_detail_rejects_inactive_products(): void
    {
        $category = ProductCategory::factory()->create([
            'is_active' => true,
        ]);
        $product = Product::factory()->create([
            'product_category_id' => $category->id,
            'is_active' => false,
        ]);

        $this->get(route('public.catalog.show', $product))
            ->assertNotFound();
    }
}
