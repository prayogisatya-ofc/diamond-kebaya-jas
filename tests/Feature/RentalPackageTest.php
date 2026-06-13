<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RentalPackage;
use App\Models\RentalPackageItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalPackageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->owner()->create();
    }

    public function test_guest_is_redirected_from_rental_packages(): void
    {
        $response = $this->get(route('rental-packages.index'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_rental_package_can_be_created_with_items(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('rental-packages.store'), [
                'name' => 'Paket Kebaya Lengkap',
                'description' => 'Paket untuk acara resmi.',
                'package_price' => 350000,
                'is_active' => true,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => 2,
                        'default_item_price' => 150000,
                        'is_optional' => false,
                        'notes' => 'Termasuk aksesoris.',
                    ],
                ],
            ])
            ->assertRedirect();

        $rentalPackage = RentalPackage::query()->where('name', 'Paket Kebaya Lengkap')->firstOrFail();
        $item = $rentalPackage->items()->firstOrFail();

        $this->assertSame('350000.00', $rentalPackage->package_price);
        $this->assertSame($product->id, $item->product_id);
        $this->assertSame($variant->id, $item->product_variant_id);
        $this->assertSame(2, $item->quantity);
        $this->assertFalse($item->is_optional);
    }

    public function test_rental_package_items_can_be_updated_safely(): void
    {
        $package = RentalPackage::factory()->create([
            'name' => 'Paket Lama',
        ]);

        $productA = Product::factory()->create(['name' => 'Jas Hitam']);
        $productB = Product::factory()->create(['name' => 'Kemeja Putih']);
        $productC = Product::factory()->create(['name' => 'Dasi']);

        $existingKept = RentalPackageItem::factory()->create([
            'rental_package_id' => $package->id,
            'product_id' => $productA->id,
            'quantity' => 1,
        ]);

        $existingRemoved = RentalPackageItem::factory()->create([
            'rental_package_id' => $package->id,
            'product_id' => $productB->id,
            'quantity' => 1,
        ]);

        $this->actingAs($this->user)
            ->put(route('rental-packages.update', $package), [
                'name' => 'Paket Jas Full Set',
                'description' => null,
                'package_price' => 500000,
                'is_active' => false,
                'items' => [
                    [
                        'id' => $existingKept->id,
                        'product_id' => $productA->id,
                        'product_variant_id' => null,
                        'quantity' => 2,
                        'default_item_price' => 250000,
                        'is_optional' => true,
                        'notes' => 'Updated.',
                    ],
                    [
                        'product_id' => $productC->id,
                        'product_variant_id' => null,
                        'quantity' => 1,
                        'default_item_price' => null,
                        'is_optional' => false,
                        'notes' => null,
                    ],
                ],
            ])
            ->assertRedirect(route('rental-packages.show', $package, absolute: false));

        $package->refresh();
        $existingKept->refresh();

        $this->assertSame('Paket Jas Full Set', $package->name);
        $this->assertFalse($package->is_active);
        $this->assertSame(2, $package->items()->count());
        $this->assertSame(2, $existingKept->quantity);
        $this->assertTrue($existingKept->is_optional);
        $this->assertModelMissing($existingRemoved);
        $this->assertTrue($package->items()->where('product_id', $productC->id)->exists());
    }

    public function test_package_item_variant_must_match_selected_product(): void
    {
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();
        $variantForProductB = ProductVariant::factory()->create([
            'product_id' => $productB->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('rental-packages.store'), [
                'name' => 'Paket Tidak Valid',
                'package_price' => 100000,
                'is_active' => true,
                'items' => [
                    [
                        'product_id' => $productA->id,
                        'product_variant_id' => $variantForProductB->id,
                        'quantity' => 1,
                        'default_item_price' => null,
                        'is_optional' => false,
                        'notes' => null,
                    ],
                ],
            ])
            ->assertSessionHasErrors('items.0.product_variant_id');
    }

    public function test_rental_package_can_be_deleted(): void
    {
        $package = RentalPackage::factory()
            ->has(RentalPackageItem::factory()->count(2), 'items')
            ->create();

        $this->actingAs($this->user)
            ->delete(route('rental-packages.destroy', $package))
            ->assertRedirect(route('rental-packages.index', absolute: false));

        $this->assertModelMissing($package);
        $this->assertSame(0, RentalPackageItem::query()->count());
    }
}
