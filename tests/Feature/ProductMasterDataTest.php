<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\RentalItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductMasterDataTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->owner()->create();
    }

    public function test_guest_is_redirected_from_product_master_data(): void
    {
        $response = $this->get(route('products.index'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_product_categories_can_be_managed(): void
    {
        $this->actingAs($this->user)
            ->post(route('product-categories.store'), [
                'name' => 'Kebaya',
                'is_active' => true,
            ])
            ->assertRedirect(route('product-categories.index', absolute: false));

        $category = ProductCategory::query()->where('name', 'Kebaya')->firstOrFail();

        $this->assertModelExists($category);
        $this->assertSame('kebaya', $category->slug);

        $this->actingAs($this->user)
            ->put(route('product-categories.update', $category), [
                'name' => 'Kebaya Premium',
                'is_active' => false,
            ])
            ->assertRedirect(route('product-categories.index', absolute: false));

        $category->refresh();

        $this->assertSame('Kebaya Premium', $category->name);
        $this->assertFalse($category->is_active);

        $this->actingAs($this->user)
            ->delete(route('product-categories.destroy', $category))
            ->assertRedirect(route('product-categories.index', absolute: false));

        $this->assertModelMissing($category);
    }

    public function test_category_with_products_can_not_be_deleted(): void
    {
        $product = Product::factory()->create();
        $category = $product->category;

        $this->actingAs($this->user)
            ->delete(route('product-categories.destroy', $category))
            ->assertRedirect(route('product-categories.index', absolute: false))
            ->assertSessionHasErrors('category');

        $this->assertModelExists($category);
    }

    public function test_products_can_be_managed_and_filtered(): void
    {
        $kebaya = ProductCategory::factory()->create(['name' => 'Kebaya', 'slug' => 'kebaya']);
        $jas = ProductCategory::factory()->create(['name' => 'Jas', 'slug' => 'jas']);

        $this->actingAs($this->user)
            ->post(route('products.store'), [
                'product_category_id' => $kebaya->id,
                'name' => 'Kebaya Merah',
                'code' => 'KB-MRH',
                'description' => 'Kebaya warna merah.',
                'base_rental_price' => 150000,
                'is_active' => true,
            ])
            ->assertRedirect();

        $product = Product::query()->where('code', 'KB-MRH')->firstOrFail();

        Product::factory()->create([
            'product_category_id' => $jas->id,
            'name' => 'Jas Hitam',
            'code' => 'JS-HTM',
        ]);

        $this->actingAs($this->user)
            ->get(route('products.index', ['search' => 'Merah', 'category' => $kebaya->id]))
            ->assertOk()
            ->assertSee('Kebaya Merah')
            ->assertDontSee('Jas Hitam');

        $this->actingAs($this->user)
            ->put(route('products.update', $product), [
                'product_category_id' => $jas->id,
                'name' => 'Kebaya Merah Update',
                'code' => 'KB-MRH-2',
                'description' => null,
                'base_rental_price' => 175000,
                'is_active' => false,
            ])
            ->assertRedirect(route('products.show', $product, absolute: false));

        $product->refresh();

        $this->assertSame($jas->id, $product->product_category_id);
        $this->assertSame('KB-MRH-2', $product->code);
        $this->assertFalse($product->is_active);

        $this->actingAs($this->user)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index', absolute: false));

        $this->assertModelMissing($product);
    }

    public function test_product_can_create_category_inline(): void
    {
        $this->actingAs($this->user)
            ->post(route('products.store'), [
                'product_category_id' => null,
                'new_product_category_name' => 'Beskap Premium',
                'name' => 'Beskap Hitam',
                'code' => 'BSP-HTM',
                'description' => 'Beskap untuk acara formal.',
                'base_rental_price' => 225000,
                'is_active' => true,
            ])
            ->assertRedirect();

        $category = ProductCategory::query()->where('name', 'Beskap Premium')->firstOrFail();
        $product = Product::query()->where('code', 'BSP-HTM')->firstOrFail();

        $this->assertSame('beskap-premium', $category->slug);
        $this->assertSame($category->id, $product->product_category_id);
    }

    public function test_product_can_create_category_inline_when_updated(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->user)
            ->put(route('products.update', $product), [
                'product_category_id' => null,
                'new_product_category_name' => 'Aksesoris Kepala',
                'name' => 'Siger Sunda',
                'code' => 'SGR-SND',
                'description' => null,
                'base_rental_price' => 100000,
                'is_active' => true,
            ])
            ->assertRedirect(route('products.show', $product, absolute: false));

        $category = ProductCategory::query()->where('name', 'Aksesoris Kepala')->firstOrFail();

        $product->refresh();

        $this->assertSame($category->id, $product->product_category_id);
        $this->assertSame('Siger Sunda', $product->name);
    }

    public function test_product_image_is_uploaded_replaced_and_deleted_with_product(): void
    {
        Storage::fake('public');

        $category = ProductCategory::factory()->create();
        $firstImage = UploadedFile::fake()->image('kebaya-awal.jpg', 800, 800);
        $replacementImage = UploadedFile::fake()->image('kebaya-baru.webp', 800, 800);

        $this->actingAs($this->user)
            ->post(route('products.store'), [
                'product_category_id' => $category->id,
                'name' => 'Kebaya Foto',
                'code' => 'KB-FOTO',
                'description' => 'Produk dengan foto.',
                'image' => $firstImage,
                'base_rental_price' => 150000,
                'is_active' => true,
            ])
            ->assertRedirect();

        $product = Product::query()->where('code', 'KB-FOTO')->firstOrFail();
        $firstImagePath = $product->image_path;

        $this->assertNotNull($firstImagePath);
        Storage::disk('public')->assertExists($firstImagePath);

        $this->actingAs($this->user)
            ->post(route('products.update', $product), [
                '_method' => 'put',
                'product_category_id' => $category->id,
                'name' => 'Kebaya Foto Update',
                'code' => 'KB-FOTO-2',
                'description' => null,
                'image' => $replacementImage,
                'base_rental_price' => 175000,
                'is_active' => true,
            ])
            ->assertRedirect(route('products.show', $product, absolute: false));

        $product->refresh();
        $replacementImagePath = $product->image_path;

        $this->assertNotNull($replacementImagePath);
        $this->assertNotSame($firstImagePath, $replacementImagePath);
        Storage::disk('public')->assertMissing($firstImagePath);
        Storage::disk('public')->assertExists($replacementImagePath);

        $this->actingAs($this->user)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index', absolute: false));

        Storage::disk('public')->assertMissing($replacementImagePath);
        $this->assertModelMissing($product);
    }

    public function test_product_used_by_rental_can_not_be_deleted_and_is_deactivated(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/kebaya.jpg', 'image-content');

        $product = Product::factory()->create([
            'image_path' => 'products/kebaya.jpg',
            'is_active' => true,
        ]);
        RentalItem::factory()->create([
            'product_id' => $product->id,
        ]);

        $this->actingAs($this->user)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index', absolute: false))
            ->assertSessionHas('warning');

        $product->refresh();

        $this->assertFalse($product->is_active);
        $this->assertModelExists($product);
        Storage::disk('public')->assertExists('products/kebaya.jpg');
    }

    public function test_product_prices_must_be_integers(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($this->user)
            ->post(route('products.store'), [
                'product_category_id' => $category->id,
                'name' => 'Kebaya Desimal',
                'code' => 'KB-DEC',
                'description' => null,
                'base_rental_price' => '150000.50',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('base_rental_price');

        $this->actingAs($this->user)
            ->post(route('products.variants.store', $product), [
                'sku' => 'SKU-DEC',
                'name' => 'Varian Desimal',
                'size' => 'M',
                'color' => 'Merah',
                'stock_quantity' => 1,
                'rental_price' => '200000.25',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('rental_price');
    }

    public function test_product_variants_can_be_managed(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->user)
            ->post(route('products.variants.store', $product), [
                'sku' => 'SKU-KB-M',
                'name' => 'Size M Merah',
                'size' => 'M',
                'color' => 'Merah',
                'stock_quantity' => 3,
                'rental_price' => 200000,
                'is_active' => true,
            ])
            ->assertRedirect(route('products.show', $product, absolute: false));

        $variant = ProductVariant::query()->where('sku', 'SKU-KB-M')->firstOrFail();

        $this->assertSame($product->id, $variant->product_id);

        $this->actingAs($this->user)
            ->put(route('product-variants.update', $variant), [
                'sku' => 'SKU-KB-L',
                'name' => 'Size L Merah',
                'size' => 'L',
                'color' => 'Merah',
                'stock_quantity' => 5,
                'rental_price' => null,
                'is_active' => false,
            ])
            ->assertRedirect(route('products.show', $product, absolute: false));

        $variant->refresh();

        $this->assertSame('Size L Merah', $variant->name);
        $this->assertSame(5, $variant->stock_quantity);
        $this->assertNull($variant->rental_price);
        $this->assertFalse($variant->is_active);

        $this->actingAs($this->user)
            ->delete(route('product-variants.destroy', $variant))
            ->assertRedirect(route('products.show', $product, absolute: false));

        $this->assertModelMissing($variant);
    }

    public function test_product_variant_used_by_rental_can_not_be_deleted_and_is_deactivated(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);
        RentalItem::factory()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
        ]);

        $this->actingAs($this->user)
            ->delete(route('product-variants.destroy', $variant))
            ->assertRedirect(route('products.show', $product, absolute: false))
            ->assertSessionHas('warning');

        $variant->refresh();

        $this->assertFalse($variant->is_active);
        $this->assertModelExists($variant);
    }
}
