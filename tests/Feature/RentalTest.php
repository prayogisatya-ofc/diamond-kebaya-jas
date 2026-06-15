<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\RentalPackage;
use App\Models\RentalPackageItem;
use App\Models\RentalPayment;
use App\Models\RentalWhatsappNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->owner()->create();
    }

    public function test_guest_is_redirected_from_rentals(): void
    {
        $response = $this->get(route('rentals.index'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_rentals_can_be_filtered_by_search_status_and_payment_status(): void
    {
        $budi = Customer::factory()->create([
            'name' => 'Budi Santoso',
            'whatsapp_number' => '0811111111',
        ]);
        $sari = Customer::factory()->create([
            'name' => 'Sari Wijaya',
            'whatsapp_number' => '0822222222',
        ]);
        $matchingRental = Rental::factory()->create([
            'customer_id' => $budi->id,
            'invoice_number' => 'INV-20260613-0001',
            'status' => 'booked',
            'payment_status' => 'dp',
            'pickup_at' => '2026-06-20 10:00:00',
        ]);
        Rental::factory()->create([
            'customer_id' => $sari->id,
            'invoice_number' => 'INV-20260613-0002',
            'status' => 'completed',
            'payment_status' => 'paid',
            'pickup_at' => '2026-06-21 10:00:00',
        ]);

        $this->actingAs($this->user)
            ->get(route('rentals.index', [
                'search' => 'Budi',
                'status' => 'booked',
                'payment_status' => 'dp',
                'pickup_from' => '2026-06-20',
                'pickup_to' => '2026-06-20',
            ]))
            ->assertOk()
            ->assertSee('INV-20260613-0001')
            ->assertSee('Budi')
            ->assertDontSee('INV-20260613-0002')
            ->assertDontSee('Sari');
    }

    public function test_rental_can_be_created_for_existing_customer_with_initial_dp(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Kebaya Merah',
            'base_rental_price' => 150000,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size M Merah',
            'stock_quantity' => 2,
            'rental_price' => 175000,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'guarantee_type' => 'ktp',
                'pickup_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'return_due_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'notes' => 'DP saat booking.',
                'custom_total_amount' => 300000,
                'initial_payment_amount' => 100000,
                'initial_payment_method' => 'cash',
                'initial_payment_notes' => 'DP tunai.',
                'items' => [
                    [
                        'rental_package_id' => null,
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => 2,
                        'unit_price' => 175000,
                        'discount_amount' => 50000,
                        'notes' => 'Set kebaya utama.',
                    ],
                ],
            ])
            ->assertRedirect();

        $rental = Rental::query()->with(['items', 'payments'])->firstOrFail();
        $item = $rental->items->first();
        $payment = $rental->payments->first();

        $this->assertSame($customer->id, $rental->customer_id);
        $this->assertStringStartsWith('INV-'.now()->format('Ymd').'-', $rental->invoice_number);
        $this->assertSame('booked', $rental->status);
        $this->assertSame('dp', $rental->payment_status);
        $this->assertSame('300000.00', $rental->subtotal_amount);
        $this->assertSame('300000.00', $rental->total_amount);
        $this->assertSame('100000.00', $rental->paid_amount);
        $this->assertSame('200000.00', $rental->remaining_amount);
        $this->assertSame('Kebaya Merah', $item->item_name_snapshot);
        $this->assertSame('Size M Merah', $item->variant_name_snapshot);
        $this->assertSame('300000.00', $item->final_price);
        $this->assertSame('dp', $payment->payment_type);
        $this->assertSame('cash', $payment->payment_method);
    }

    public function test_rental_can_be_created_without_guarantee_type(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'base_rental_price' => 150000,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 1,
            'rental_price' => 150000,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'guarantee_type' => null,
                'pickup_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'return_due_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => 1,
                        'unit_price' => 150000,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertNull(Rental::query()->firstOrFail()->guarantee_type);
    }

    public function test_rental_can_create_new_customer_and_store_package_item_snapshot(): void
    {
        $package = RentalPackage::factory()->create([
            'name' => 'Paket Jas Full Set',
        ]);
        $product = Product::factory()->create([
            'name' => 'Jas Hitam',
            'base_rental_price' => 200000,
        ]);

        RentalPackageItem::factory()->create([
            'rental_package_id' => $package->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => 1,
            'default_item_price' => 200000,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.store'), [
                'customer_mode' => 'new',
                'new_customer' => [
                    'name' => 'Andi Wijaya',
                    'whatsapp_number' => '081299988877',
                    'notes' => 'Customer baru dari walk-in.',
                ],
                'guarantee_type' => 'sim',
                'pickup_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'return_due_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'custom_total_amount' => null,
                'initial_payment_amount' => null,
                'initial_payment_method' => null,
                'items' => [
                    [
                        'rental_package_id' => $package->id,
                        'product_id' => $product->id,
                        'product_variant_id' => null,
                        'quantity' => 1,
                        'unit_price' => 200000,
                        'discount_amount' => 0,
                        'notes' => 'Disalin dari paket.',
                    ],
                ],
            ])
            ->assertRedirect();

        $customer = Customer::query()->where('whatsapp_number', '081299988877')->firstOrFail();
        $rental = Rental::query()->with('items')->whereBelongsTo($customer)->firstOrFail();
        $item = $rental->items->first();

        $this->assertSame('Andi Wijaya', $customer->name);
        $this->assertSame('sim', $rental->guarantee_type);
        $this->assertSame('unpaid', $rental->payment_status);
        $this->assertSame('200000.00', $rental->total_amount);
        $this->assertSame($package->id, $item->rental_package_id);
        $this->assertSame('Jas Hitam', $item->item_name_snapshot);
    }

    public function test_rental_item_variant_must_match_selected_product(): void
    {
        $customer = Customer::factory()->create();
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();
        $variantForProductB = ProductVariant::factory()->create([
            'product_id' => $productB->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'guarantee_type' => 'ktp',
                'pickup_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'return_due_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'items' => [
                    [
                        'product_id' => $productA->id,
                        'product_variant_id' => $variantForProductB->id,
                        'quantity' => 1,
                        'unit_price' => 150000,
                        'discount_amount' => 0,
                    ],
                ],
            ])
            ->assertSessionHasErrors('items.0.product_variant_id');
    }

    public function test_rental_detail_can_be_rendered_with_items_and_payments(): void
    {
        $customer = Customer::factory()->create(['name' => 'Rina Kartika']);
        $rental = Rental::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $this->user->id,
            'invoice_number' => 'INV-20260607-9999',
            'total_amount' => 250000,
            'paid_amount' => 100000,
            'remaining_amount' => 150000,
        ]);

        $product = Product::factory()->create(['name' => 'Rok Batik']);
        $rental->items()->create([
            'product_id' => $product->id,
            'item_name_snapshot' => 'Rok Batik',
            'quantity' => 1,
            'unit_price' => 250000,
            'discount_amount' => 0,
            'final_price' => 250000,
        ]);
        $rental->payments()->create([
            'payment_type' => 'dp',
            'payment_method' => 'transfer',
            'amount' => 100000,
            'paid_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('rentals.show', $rental))
            ->assertOk()
            ->assertSee('INV-20260607-9999')
            ->assertSee('Rina Kartika')
            ->assertSee('Rok Batik')
            ->assertSee('transfer');
    }

    public function test_rental_invoice_can_be_rendered_for_printing(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Rina Kartika',
            'whatsapp_number' => '081234567890',
        ]);
        $rental = Rental::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $this->user->id,
            'invoice_number' => 'INV-20260607-PRINT',
            'guarantee_type' => 'ktp',
            'pickup_at' => '2026-06-07 10:00:00',
            'return_due_at' => '2026-06-09 17:00:00',
            'subtotal_amount' => 300000,
            'discount_amount' => 25000,
            'custom_adjustment_amount' => 10000,
            'penalty_amount' => 15000,
            'total_amount' => 300000,
            'paid_amount' => 100000,
            'remaining_amount' => 200000,
            'notes' => 'Harap dikembalikan tepat waktu.',
        ]);
        $product = Product::factory()->create(['name' => 'Kebaya Merah']);
        $rental->items()->create([
            'product_id' => $product->id,
            'item_name_snapshot' => 'Kebaya Merah',
            'variant_name_snapshot' => 'Size M',
            'quantity' => 2,
            'unit_price' => 150000,
            'discount_amount' => 25000,
            'final_price' => 275000,
        ]);
        $rental->payments()->create([
            'payment_type' => 'dp',
            'payment_method' => 'transfer',
            'amount' => 100000,
            'paid_at' => '2026-06-07 11:00:00',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('rentals.invoice', $rental))
            ->assertOk()
            ->assertSee('Diamond Kebaya & Jas', false)
            ->assertSee('INV-20260607-PRINT')
            ->assertSee('Rina Kartika')
            ->assertSee('081234567890')
            ->assertSee('Kebaya Merah')
            ->assertSee('Size M')
            ->assertSee('transfer')
            ->assertSee('Harap dikembalikan tepat waktu.');
    }

    public function test_complete_mvp_flow_from_product_to_invoice(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Dewi Anggraini',
            'whatsapp_number' => '081211112222',
        ]);
        $product = Product::factory()->create([
            'name' => 'Kebaya Gold',
            'base_rental_price' => 300000,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size M Gold',
            'stock_quantity' => 1,
            'rental_price' => 300000,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'guarantee_type' => 'ktp',
                'pickup_at' => '2026-06-10 10:00:00',
                'return_due_at' => '2026-06-12 17:00:00',
                'custom_total_amount' => 300000,
                'initial_payment_amount' => 100000,
                'initial_payment_method' => 'cash',
                'initial_payment_notes' => 'DP booking.',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => 1,
                        'unit_price' => 300000,
                        'discount_amount' => 0,
                        'notes' => 'Item utama.',
                    ],
                ],
            ])
            ->assertRedirect();

        $rental = Rental::query()->with(['items', 'payments'])->firstOrFail();

        $this->assertSame('booked', $rental->status);
        $this->assertSame('dp', $rental->payment_status);
        $this->assertSame('100000.00', $rental->paid_amount);
        $this->assertSame('200000.00', $rental->remaining_amount);
        $this->assertSame('Kebaya Gold', $rental->items->first()->item_name_snapshot);

        $this->actingAs($this->user)
            ->post(route('rentals.payments.store', $rental), [
                'payment_type' => 'pelunasan',
                'payment_method' => 'qris',
                'amount' => 200000,
                'paid_at' => '2026-06-10 09:30:00',
                'notes' => 'Pelunasan sebelum ambil.',
            ])
            ->assertRedirect(route('rentals.show', $rental, absolute: false));

        $this->assertSame('paid', $rental->refresh()->payment_status);

        $this->actingAs($this->user)
            ->post(route('rentals.pick-up', $rental))
            ->assertRedirect(route('rentals.show', $rental, absolute: false));

        $this->assertSame('picked_up', $rental->refresh()->status);

        $this->actingAs($this->user)
            ->post(route('rentals.return', $rental), [
                'returned_at' => '2026-06-13 10:00:00',
                'penalty_amount' => 25000,
                'penalty_payment_method' => 'cash',
                'penalty_paid_at' => '2026-06-13 10:05:00',
                'penalty_notes' => 'Denda manual dibayar.',
            ])
            ->assertRedirect(route('rentals.show', $rental, absolute: false));

        $rental->refresh();

        $this->assertSame('returned', $rental->status);
        $this->assertSame(1, $rental->penalty_days);
        $this->assertSame('325000.00', $rental->total_amount);
        $this->assertSame('0.00', $rental->remaining_amount);
        $this->assertSame('paid', $rental->payment_status);

        $this->actingAs($this->user)
            ->post(route('rentals.complete', $rental))
            ->assertRedirect(route('rentals.show', $rental, absolute: false));

        $this->assertSame('completed', $rental->refresh()->status);

        $this->actingAs($this->user)
            ->get(route('rentals.invoice', $rental))
            ->assertOk()
            ->assertSee($rental->invoice_number)
            ->assertSee('Dewi Anggraini')
            ->assertSee('Kebaya Gold')
            ->assertSee('denda');
    }

    public function test_rental_snapshots_remain_safe_when_master_prices_change(): void
    {
        $customer = Customer::factory()->create();
        $package = RentalPackage::factory()->create([
            'name' => 'Paket Kebaya Lama',
            'package_price' => 275000,
        ]);
        $product = Product::factory()->create([
            'name' => 'Kebaya Snapshot',
            'base_rental_price' => 275000,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size M Snapshot',
            'stock_quantity' => 2,
            'rental_price' => 275000,
        ]);
        RentalPackageItem::factory()->create([
            'rental_package_id' => $package->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'default_item_price' => 275000,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'guarantee_type' => 'sim',
                'pickup_at' => '2026-06-20 10:00:00',
                'return_due_at' => '2026-06-22 17:00:00',
                'items' => [
                    [
                        'rental_package_id' => $package->id,
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => 1,
                        'unit_price' => 275000,
                        'discount_amount' => 0,
                        'notes' => 'Snapshot awal.',
                    ],
                ],
            ])
            ->assertRedirect();

        $rental = Rental::query()->with('items')->firstOrFail();
        $item = $rental->items->first();

        $product->update([
            'name' => 'Kebaya Snapshot Baru',
            'base_rental_price' => 500000,
        ]);
        $variant->update([
            'name' => 'Size L Snapshot Baru',
            'rental_price' => 550000,
        ]);
        $package->update([
            'name' => 'Paket Kebaya Baru',
            'package_price' => 600000,
        ]);

        $item->refresh();
        $rental->refresh();

        $this->assertSame('Kebaya Snapshot', $item->item_name_snapshot);
        $this->assertSame('Size M Snapshot', $item->variant_name_snapshot);
        $this->assertSame('275000.00', $item->unit_price);
        $this->assertSame('275000.00', $item->final_price);
        $this->assertSame('275000.00', $rental->subtotal_amount);
        $this->assertSame('275000.00', $rental->total_amount);
    }

    public function test_create_rental_blocks_double_booking_for_overlapping_variant_stock(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['name' => 'Kebaya Silver']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size M',
            'stock_quantity' => 1,
            'rental_price' => 150000,
        ]);
        $existingRental = Rental::factory()->create([
            'status' => 'booked',
            'pickup_at' => '2026-06-10 10:00:00',
            'return_due_at' => '2026-06-12 10:00:00',
        ]);
        $existingRental->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'item_name_snapshot' => $product->name,
            'variant_name_snapshot' => $variant->name,
            'quantity' => 1,
            'unit_price' => 150000,
            'discount_amount' => 0,
            'final_price' => 150000,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'guarantee_type' => 'ktp',
                'pickup_at' => '2026-06-11 09:00:00',
                'return_due_at' => '2026-06-13 09:00:00',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => 1,
                        'unit_price' => 150000,
                        'discount_amount' => 0,
                    ],
                ],
            ])
            ->assertSessionHasErrors('items.0.product_variant_id');

        $this->assertSame(1, Rental::query()->count());
    }

    public function test_returned_completed_and_cancelled_rentals_do_not_block_stock(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['name' => 'Jas Navy']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size L',
            'stock_quantity' => 1,
            'rental_price' => 250000,
        ]);

        foreach (['returned', 'completed', 'cancelled'] as $status) {
            $rental = Rental::factory()->create([
                'status' => $status,
                'pickup_at' => '2026-06-10 10:00:00',
                'return_due_at' => '2026-06-12 10:00:00',
            ]);
            $rental->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'item_name_snapshot' => $product->name,
                'variant_name_snapshot' => $variant->name,
                'quantity' => 1,
                'unit_price' => 250000,
                'discount_amount' => 0,
                'final_price' => 250000,
            ]);
        }

        $this->actingAs($this->user)
            ->post(route('rentals.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'guarantee_type' => 'sim',
                'pickup_at' => '2026-06-11 09:00:00',
                'return_due_at' => '2026-06-13 09:00:00',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => 1,
                        'unit_price' => 250000,
                        'discount_amount' => 0,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(4, Rental::query()->count());
    }

    public function test_non_overlapping_rental_does_not_block_stock(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['name' => 'Rok Hitam']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size S',
            'stock_quantity' => 1,
            'rental_price' => 100000,
        ]);
        $existingRental = Rental::factory()->create([
            'status' => 'picked_up',
            'pickup_at' => '2026-06-10 10:00:00',
            'return_due_at' => '2026-06-12 10:00:00',
        ]);
        $existingRental->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'item_name_snapshot' => $product->name,
            'variant_name_snapshot' => $variant->name,
            'quantity' => 1,
            'unit_price' => 100000,
            'discount_amount' => 0,
            'final_price' => 100000,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'guarantee_type' => 'ktp',
                'pickup_at' => '2026-06-12 10:00:00',
                'return_due_at' => '2026-06-13 10:00:00',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => 1,
                        'unit_price' => 100000,
                        'discount_amount' => 0,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(2, Rental::query()->count());
    }

    public function test_duplicate_variant_lines_in_same_request_are_counted_together(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['name' => 'Dasi Hitam']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Regular',
            'stock_quantity' => 1,
            'rental_price' => 30000,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'guarantee_type' => 'ktp',
                'pickup_at' => '2026-06-15 10:00:00',
                'return_due_at' => '2026-06-16 10:00:00',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => 1,
                        'unit_price' => 30000,
                        'discount_amount' => 0,
                    ],
                    [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => 1,
                        'unit_price' => 30000,
                        'discount_amount' => 0,
                    ],
                ],
            ])
            ->assertSessionHasErrors('items.0.product_variant_id');

        $this->assertSame(0, Rental::query()->count());
    }

    public function test_add_item_checks_variant_availability_for_rental_period(): void
    {
        $product = Product::factory()->create(['name' => 'Sepatu Pantofel']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size 42',
            'stock_quantity' => 1,
            'rental_price' => 75000,
        ]);
        $rental = Rental::factory()->create([
            'status' => 'booked',
            'pickup_at' => '2026-06-20 10:00:00',
            'return_due_at' => '2026-06-21 10:00:00',
            'subtotal_amount' => 75000,
            'total_amount' => 75000,
            'paid_amount' => 0,
            'remaining_amount' => 75000,
        ]);
        $rental->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'item_name_snapshot' => $product->name,
            'variant_name_snapshot' => $variant->name,
            'quantity' => 1,
            'unit_price' => 75000,
            'discount_amount' => 0,
            'final_price' => 75000,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.items.store', $rental), [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
                'unit_price' => 75000,
                'discount_amount' => 0,
            ])
            ->assertSessionHasErrors('product_variant_id');

        $this->assertSame(1, $rental->items()->count());
    }

    public function test_booked_rental_item_can_be_updated_and_totals_are_refreshed(): void
    {
        $product = Product::factory()->create(['name' => 'Kebaya Pink']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size M',
            'stock_quantity' => 2,
            'rental_price' => 75000,
        ]);
        $rental = Rental::factory()->create([
            'status' => 'booked',
            'pickup_at' => '2026-06-20 10:00:00',
            'return_due_at' => '2026-06-21 10:00:00',
            'subtotal_amount' => 100000,
            'total_amount' => 100000,
            'paid_amount' => 50000,
            'remaining_amount' => 50000,
            'payment_status' => 'dp',
        ]);
        $item = $rental->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'item_name_snapshot' => 'Kebaya Pink',
            'variant_name_snapshot' => 'Size M',
            'quantity' => 1,
            'unit_price' => 100000,
            'discount_amount' => 0,
            'final_price' => 100000,
        ]);

        $this->actingAs($this->user)
            ->put(route('rentals.items.update', [$rental, $item]), [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 2,
                'unit_price' => 75000,
                'discount_amount' => 10000,
                'notes' => 'Harga disesuaikan.',
            ])
            ->assertRedirect(route('rentals.show', $rental));

        $item->refresh();
        $rental->refresh();

        $this->assertSame(2, $item->quantity);
        $this->assertEquals('75000.00', $item->unit_price);
        $this->assertEquals('10000.00', $item->discount_amount);
        $this->assertEquals('140000.00', $item->final_price);
        $this->assertSame('Harga disesuaikan.', $item->notes);
        $this->assertEquals('140000.00', $rental->subtotal_amount);
        $this->assertEquals('140000.00', $rental->total_amount);
        $this->assertEquals('90000.00', $rental->remaining_amount);
        $this->assertSame('dp', $rental->payment_status);
    }

    public function test_picked_up_rental_item_can_not_be_updated(): void
    {
        $product = Product::factory()->create(['name' => 'Jas Hitam']);
        $rental = Rental::factory()->create([
            'status' => 'picked_up',
            'subtotal_amount' => 100000,
            'total_amount' => 100000,
            'remaining_amount' => 100000,
        ]);
        $item = $rental->items()->create([
            'product_id' => $product->id,
            'item_name_snapshot' => 'Jas Hitam',
            'quantity' => 1,
            'unit_price' => 100000,
            'discount_amount' => 0,
            'final_price' => 100000,
        ]);

        $this->actingAs($this->user)
            ->put(route('rentals.items.update', [$rental, $item]), [
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 100000,
                'discount_amount' => 0,
            ])
            ->assertSessionHasErrors('items');

        $item->refresh();

        $this->assertSame(1, $item->quantity);
        $this->assertEquals('100000.00', $item->final_price);
    }

    public function test_booked_rental_item_can_be_deleted_and_totals_are_refreshed(): void
    {
        $product = Product::factory()->create(['name' => 'Kebaya Pink']);
        $rental = Rental::factory()->create([
            'status' => 'booked',
            'subtotal_amount' => 150000,
            'total_amount' => 150000,
            'paid_amount' => 50000,
            'remaining_amount' => 100000,
            'payment_status' => 'dp',
        ]);
        $firstItem = $rental->items()->create([
            'product_id' => $product->id,
            'item_name_snapshot' => 'Kebaya Pink',
            'quantity' => 1,
            'unit_price' => 100000,
            'discount_amount' => 0,
            'final_price' => 100000,
        ]);
        $secondItem = $rental->items()->create([
            'product_id' => $product->id,
            'item_name_snapshot' => 'Selendang',
            'quantity' => 1,
            'unit_price' => 50000,
            'discount_amount' => 0,
            'final_price' => 50000,
        ]);

        $this->actingAs($this->user)
            ->delete(route('rentals.items.destroy', [$rental, $secondItem]))
            ->assertRedirect(route('rentals.show', $rental));

        $this->assertDatabaseMissing('rental_items', ['id' => $secondItem->id]);
        $this->assertDatabaseHas('rental_items', ['id' => $firstItem->id]);

        $rental->refresh();

        $this->assertEquals('100000.00', $rental->subtotal_amount);
        $this->assertEquals('100000.00', $rental->total_amount);
        $this->assertEquals('50000.00', $rental->remaining_amount);
        $this->assertSame('dp', $rental->payment_status);
    }

    public function test_picked_up_rental_item_can_not_be_deleted(): void
    {
        $product = Product::factory()->create(['name' => 'Jas Hitam']);
        $rental = Rental::factory()->create([
            'status' => 'picked_up',
            'subtotal_amount' => 100000,
            'total_amount' => 100000,
            'remaining_amount' => 100000,
        ]);
        $item = $rental->items()->create([
            'product_id' => $product->id,
            'item_name_snapshot' => 'Jas Hitam',
            'quantity' => 1,
            'unit_price' => 100000,
            'discount_amount' => 0,
            'final_price' => 100000,
        ]);

        $this->actingAs($this->user)
            ->delete(route('rentals.items.destroy', [$rental, $item]))
            ->assertSessionHasErrors('items');

        $this->assertDatabaseHas('rental_items', ['id' => $item->id]);
    }

    public function test_availability_endpoint_returns_available_stock_for_period(): void
    {
        $product = Product::factory()->create(['name' => 'Kemeja Putih']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size XL',
            'stock_quantity' => 3,
            'rental_price' => 90000,
        ]);
        $rental = Rental::factory()->create([
            'status' => 'overdue',
            'pickup_at' => '2026-06-20 10:00:00',
            'return_due_at' => '2026-06-22 10:00:00',
        ]);
        $rental->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'item_name_snapshot' => $product->name,
            'variant_name_snapshot' => $variant->name,
            'quantity' => 2,
            'unit_price' => 90000,
            'discount_amount' => 0,
            'final_price' => 180000,
        ]);

        $this->actingAs($this->user)
            ->getJson(route('rental-availability', [
                'product_variant_id' => $variant->id,
                'pickup_at' => '2026-06-21 10:00:00',
                'return_due_at' => '2026-06-23 10:00:00',
            ]))
            ->assertOk()
            ->assertJsonPath('availability.stock_quantity', 3)
            ->assertJsonPath('availability.booked_quantity', 2)
            ->assertJsonPath('availability.available_quantity', 1);
    }

    public function test_payment_dp_updates_rental_payment_summary(): void
    {
        $rental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'total_amount' => 500000,
            'paid_amount' => 0,
            'remaining_amount' => 500000,
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.payments.store', $rental), [
                'payment_type' => 'dp',
                'payment_method' => 'transfer',
                'amount' => 200000,
                'paid_at' => '2026-06-07 10:00:00',
                'notes' => 'DP transfer.',
            ])
            ->assertRedirect(route('rentals.show', $rental, absolute: false));

        $rental->refresh();
        $payment = $rental->payments()->firstOrFail();

        $this->assertSame('200000.00', $rental->paid_amount);
        $this->assertSame('300000.00', $rental->remaining_amount);
        $this->assertSame('dp', $rental->payment_status);
        $this->assertSame('dp', $payment->payment_type);
        $this->assertSame('transfer', $payment->payment_method);
        $this->assertSame($this->user->id, $payment->created_by);
    }

    public function test_pelunasan_updates_payment_status_to_paid(): void
    {
        $rental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'total_amount' => 500000,
            'paid_amount' => 200000,
            'remaining_amount' => 300000,
            'payment_status' => 'dp',
        ]);
        $rental->payments()->create([
            'payment_type' => 'dp',
            'payment_method' => 'cash',
            'amount' => 200000,
            'paid_at' => '2026-06-07 09:00:00',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.payments.store', $rental), [
                'payment_type' => 'pelunasan',
                'payment_method' => 'qris',
                'amount' => 300000,
                'paid_at' => '2026-06-07 11:00:00',
                'notes' => null,
            ])
            ->assertRedirect(route('rentals.show', $rental, absolute: false));

        $rental->refresh();

        $this->assertSame('500000.00', $rental->paid_amount);
        $this->assertSame('0.00', $rental->remaining_amount);
        $this->assertSame('paid', $rental->payment_status);
        $this->assertSame(2, $rental->payments()->count());
    }

    public function test_overpayment_sets_payment_status_to_overpaid(): void
    {
        $rental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'total_amount' => 250000,
            'paid_amount' => 0,
            'remaining_amount' => 250000,
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.payments.store', $rental), [
                'payment_type' => 'pelunasan',
                'payment_method' => 'cash',
                'amount' => 300000,
                'paid_at' => '2026-06-07 12:00:00',
            ])
            ->assertRedirect(route('rentals.show', $rental, absolute: false));

        $rental->refresh();

        $this->assertSame('300000.00', $rental->paid_amount);
        $this->assertSame('-50000.00', $rental->remaining_amount);
        $this->assertSame('overpaid', $rental->payment_status);
    }

    public function test_refund_payment_reduces_paid_amount_without_deleting_history(): void
    {
        $rental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'total_amount' => 500000,
            'paid_amount' => 500000,
            'remaining_amount' => 0,
            'payment_status' => 'paid',
        ]);
        $rental->payments()->create([
            'payment_type' => 'pelunasan',
            'payment_method' => 'transfer',
            'amount' => 500000,
            'paid_at' => '2026-06-07 09:00:00',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.payments.store', $rental), [
                'payment_type' => 'refund',
                'payment_method' => 'transfer',
                'amount' => 100000,
                'paid_at' => '2026-06-07 13:00:00',
                'notes' => 'Koreksi refund.',
            ])
            ->assertRedirect(route('rentals.show', $rental, absolute: false));

        $rental->refresh();

        $this->assertSame('400000.00', $rental->paid_amount);
        $this->assertSame('100000.00', $rental->remaining_amount);
        $this->assertSame('dp', $rental->payment_status);
        $this->assertSame(2, $rental->payments()->count());
    }

    public function test_payment_can_be_deleted_and_payment_summary_is_refreshed(): void
    {
        $rental = Rental::factory()->create([
            'status' => 'booked',
            'total_amount' => 500000,
            'paid_amount' => 500000,
            'remaining_amount' => 0,
            'payment_status' => 'paid',
        ]);
        $dpPayment = $rental->payments()->create([
            'payment_type' => 'dp',
            'payment_method' => 'cash',
            'amount' => 200000,
            'paid_at' => '2026-06-07 09:00:00',
            'created_by' => $this->user->id,
        ]);
        $pelunasanPayment = $rental->payments()->create([
            'payment_type' => 'pelunasan',
            'payment_method' => 'transfer',
            'amount' => 300000,
            'paid_at' => '2026-06-07 11:00:00',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->delete(route('rentals.payments.destroy', [$rental, $pelunasanPayment]))
            ->assertRedirect(route('rentals.show', $rental));

        $this->assertDatabaseMissing('rental_payments', ['id' => $pelunasanPayment->id]);
        $this->assertDatabaseHas('rental_payments', ['id' => $dpPayment->id]);

        $rental->refresh();

        $this->assertEquals('200000.00', $rental->paid_amount);
        $this->assertEquals('300000.00', $rental->remaining_amount);
        $this->assertSame('dp', $rental->payment_status);
    }

    public function test_completed_rental_payment_can_not_be_deleted(): void
    {
        $rental = Rental::factory()->create([
            'status' => 'completed',
            'total_amount' => 500000,
            'paid_amount' => 500000,
            'remaining_amount' => 0,
            'payment_status' => 'paid',
        ]);
        $payment = $rental->payments()->create([
            'payment_type' => 'pelunasan',
            'payment_method' => 'cash',
            'amount' => 500000,
            'paid_at' => '2026-06-07 09:00:00',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->delete(route('rentals.payments.destroy', [$rental, $payment]))
            ->assertSessionHasErrors('payments');

        $this->assertDatabaseHas('rental_payments', ['id' => $payment->id]);
    }

    public function test_payment_validation_requires_valid_type_method_amount_and_date(): void
    {
        $rental = Rental::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.payments.store', $rental), [
                'payment_type' => 'invalid',
                'payment_method' => 'invalid',
                'amount' => 0,
                'paid_at' => null,
            ])
            ->assertSessionHasErrors(['payment_type', 'payment_method', 'amount', 'paid_at']);
    }

    public function test_rental_can_follow_booked_picked_up_returned_completed_flow(): void
    {
        $rental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'booked',
            'payment_status' => 'paid',
            'subtotal_amount' => 500000,
            'total_amount' => 500000,
            'paid_amount' => 500000,
            'remaining_amount' => 0,
            'picked_up_at' => null,
            'returned_at' => null,
        ]);
        RentalPayment::factory()->create([
            'rental_id' => $rental->id,
            'payment_type' => 'pelunasan',
            'payment_method' => 'cash',
            'amount' => 500000,
            'paid_at' => '2026-06-07 12:00:00',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.pick-up', $rental))
            ->assertRedirect(route('rentals.show', $rental, absolute: false));

        $rental->refresh();

        $this->assertSame('picked_up', $rental->status);
        $this->assertNotNull($rental->picked_up_at);
        $this->assertSame($this->user->id, $rental->picked_up_by);

        $this->actingAs($this->user)
            ->post(route('rentals.return', $rental), [
                'returned_at' => '2026-06-07 15:30:00',
            ])
            ->assertRedirect(route('rentals.show', $rental, absolute: false));

        $rental->refresh();

        $this->assertSame('returned', $rental->status);
        $this->assertSame('2026-06-07 15:30:00', $rental->returned_at->format('Y-m-d H:i:s'));
        $this->assertSame($this->user->id, $rental->returned_by);

        $this->actingAs($this->user)
            ->post(route('rentals.complete', $rental))
            ->assertRedirect(route('rentals.show', $rental, absolute: false));

        $this->assertSame('completed', $rental->refresh()->status);
    }

    public function test_rental_without_guarantee_requires_guarantee_when_picked_up(): void
    {
        $rental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'booked',
            'guarantee_type' => null,
        ]);

        $this->actingAs($this->user)
            ->from(route('rentals.show', $rental))
            ->post(route('rentals.pick-up', $rental))
            ->assertRedirect(route('rentals.show', $rental, absolute: false))
            ->assertSessionHasErrors('guarantee_type');

        $this->assertSame('booked', $rental->refresh()->status);
        $this->assertNull($rental->guarantee_type);
    }

    public function test_rental_guarantee_can_be_filled_when_picked_up(): void
    {
        $rental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'booked',
            'guarantee_type' => null,
            'payment_status' => 'paid',
            'subtotal_amount' => 300000,
            'total_amount' => 300000,
            'paid_amount' => 300000,
            'remaining_amount' => 0,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.pick-up', $rental), [
                'guarantee_type' => 'sim',
            ])
            ->assertRedirect(route('rentals.show', $rental, absolute: false));

        $rental->refresh();

        $this->assertSame('picked_up', $rental->status);
        $this->assertSame('sim', $rental->guarantee_type);
        $this->assertNotNull($rental->picked_up_at);
        $this->assertSame($this->user->id, $rental->picked_up_by);
    }

    public function test_pick_up_records_required_pelunasan_when_rental_has_remaining_payment(): void
    {
        $rental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'booked',
            'guarantee_type' => 'ktp',
            'payment_status' => 'dp',
            'subtotal_amount' => 500000,
            'total_amount' => 500000,
            'paid_amount' => 200000,
            'remaining_amount' => 300000,
        ]);
        RentalPayment::factory()->create([
            'rental_id' => $rental->id,
            'payment_type' => 'dp',
            'payment_method' => 'cash',
            'amount' => 200000,
            'paid_at' => '2026-06-09 10:00:00',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.pick-up', $rental), [
                'payment_amount' => 300000,
                'payment_method' => 'qris',
                'paid_at' => '2026-06-10 10:00:00',
                'payment_notes' => 'Pelunasan saat ambil.',
            ])
            ->assertRedirect(route('rentals.show', $rental, absolute: false));

        $rental->refresh();
        $payment = $rental->payments()->where('payment_type', 'pelunasan')->firstOrFail();

        $this->assertSame('picked_up', $rental->status);
        $this->assertSame('paid', $rental->payment_status);
        $this->assertSame('500000.00', $rental->paid_amount);
        $this->assertSame('0.00', $rental->remaining_amount);
        $this->assertSame('qris', $payment->payment_method);
        $this->assertSame('300000.00', $payment->amount);
    }

    public function test_return_on_time_stores_zero_penalty(): void
    {
        $rental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'picked_up',
            'pickup_at' => '2026-06-08 10:00:00',
            'return_due_at' => '2026-06-10 17:00:00',
            'payment_status' => 'paid',
            'subtotal_amount' => 500000,
            'total_amount' => 500000,
            'paid_amount' => 500000,
            'remaining_amount' => 0,
        ]);
        RentalPayment::factory()->create([
            'rental_id' => $rental->id,
            'payment_type' => 'pelunasan',
            'payment_method' => 'cash',
            'amount' => 500000,
            'paid_at' => '2026-06-08 10:00:00',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.return', $rental), [
                'returned_at' => '2026-06-10 16:59:00',
                'penalty_amount' => 0,
            ])
            ->assertRedirect(route('rentals.show', $rental, absolute: false));

        $rental->refresh();

        $this->assertSame('returned', $rental->status);
        $this->assertSame(0, $rental->penalty_days);
        $this->assertSame('0.00', $rental->penalty_amount);
        $this->assertSame('500000.00', $rental->total_amount);
        $this->assertSame('0.00', $rental->remaining_amount);
        $this->assertSame('paid', $rental->payment_status);
    }

    public function test_penalty_days_are_calculated_by_calendar_date_only(): void
    {
        $sameDateRental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'picked_up',
            'pickup_at' => '2026-06-14 10:00:00',
            'return_due_at' => '2026-06-15 08:00:00',
            'payment_status' => 'paid',
            'subtotal_amount' => 300000,
            'total_amount' => 300000,
            'paid_amount' => 300000,
            'remaining_amount' => 0,
        ]);
        RentalPayment::factory()->create([
            'rental_id' => $sameDateRental->id,
            'payment_type' => 'pelunasan',
            'payment_method' => 'cash',
            'amount' => 300000,
            'paid_at' => '2026-06-14 10:00:00',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.return', $sameDateRental), [
                'returned_at' => '2026-06-15 23:59:00',
                'penalty_amount' => 0,
            ])
            ->assertRedirect(route('rentals.show', $sameDateRental, absolute: false));

        $this->assertSame(0, $sameDateRental->refresh()->penalty_days);

        $nextDateRental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'picked_up',
            'pickup_at' => '2026-06-14 10:00:00',
            'return_due_at' => '2026-06-15 23:00:00',
            'payment_status' => 'paid',
            'subtotal_amount' => 300000,
            'total_amount' => 300000,
            'paid_amount' => 300000,
            'remaining_amount' => 0,
        ]);
        RentalPayment::factory()->create([
            'rental_id' => $nextDateRental->id,
            'payment_type' => 'pelunasan',
            'payment_method' => 'cash',
            'amount' => 300000,
            'paid_at' => '2026-06-14 10:00:00',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.return', $nextDateRental), [
                'returned_at' => '2026-06-16 01:00:00',
                'penalty_amount' => 25000,
                'penalty_payment_method' => 'cash',
                'penalty_paid_at' => '2026-06-16 01:05:00',
            ])
            ->assertRedirect(route('rentals.show', $nextDateRental, absolute: false));

        $this->assertSame(1, $nextDateRental->refresh()->penalty_days);
    }

    public function test_late_return_requires_manual_penalty_payment(): void
    {
        $rental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'picked_up',
            'pickup_at' => '2026-06-08 10:00:00',
            'return_due_at' => '2026-06-10 17:00:00',
            'payment_status' => 'paid',
            'subtotal_amount' => 500000,
            'total_amount' => 500000,
            'paid_amount' => 500000,
            'remaining_amount' => 0,
        ]);
        RentalPayment::factory()->create([
            'rental_id' => $rental->id,
            'payment_type' => 'pelunasan',
            'payment_method' => 'transfer',
            'amount' => 500000,
            'paid_at' => '2026-06-08 10:00:00',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.return', $rental), [
                'returned_at' => '2026-06-11 10:00:00',
                'penalty_amount' => 25000,
            ])
            ->assertSessionHasErrors(['penalty_payment_method', 'penalty_paid_at']);

        $this->assertSame('picked_up', $rental->refresh()->status);
        $this->assertSame(0, $rental->payments()->where('payment_type', 'denda')->count());
    }

    public function test_late_return_records_manual_penalty_payment_automatically(): void
    {
        $rental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'picked_up',
            'pickup_at' => '2026-06-08 10:00:00',
            'return_due_at' => '2026-06-10 17:00:00',
            'payment_status' => 'paid',
            'subtotal_amount' => 500000,
            'total_amount' => 500000,
            'paid_amount' => 500000,
            'remaining_amount' => 0,
        ]);
        RentalPayment::factory()->create([
            'rental_id' => $rental->id,
            'payment_type' => 'pelunasan',
            'payment_method' => 'transfer',
            'amount' => 500000,
            'paid_at' => '2026-06-08 10:00:00',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.return', $rental), [
                'returned_at' => '2026-06-11 10:00:00',
                'penalty_amount' => 25000,
                'penalty_payment_method' => 'cash',
                'penalty_paid_at' => '2026-06-11 10:05:00',
                'penalty_notes' => 'Denda dibayar saat return.',
            ])
            ->assertRedirect(route('rentals.show', $rental, absolute: false));

        $rental->refresh();
        $penaltyPayment = $rental->payments()->where('payment_type', 'denda')->firstOrFail();

        $this->assertSame('returned', $rental->status);
        $this->assertSame(1, $rental->penalty_days);
        $this->assertSame('25000.00', $rental->penalty_amount);
        $this->assertSame('525000.00', $rental->total_amount);
        $this->assertSame('525000.00', $rental->paid_amount);
        $this->assertSame('0.00', $rental->remaining_amount);
        $this->assertSame('paid', $rental->payment_status);
        $this->assertSame('cash', $penaltyPayment->payment_method);
    }

    public function test_late_return_can_record_manual_penalty_payment_immediately(): void
    {
        $rental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'overdue',
            'pickup_at' => '2026-06-08 10:00:00',
            'return_due_at' => '2026-06-10 17:00:00',
            'payment_status' => 'paid',
            'subtotal_amount' => 500000,
            'total_amount' => 500000,
            'paid_amount' => 500000,
            'remaining_amount' => 0,
        ]);
        RentalPayment::factory()->create([
            'rental_id' => $rental->id,
            'payment_type' => 'pelunasan',
            'payment_method' => 'qris',
            'amount' => 500000,
            'paid_at' => '2026-06-08 10:00:00',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.return', $rental), [
                'returned_at' => '2026-06-13 18:00:00',
                'penalty_amount' => 75000,
                'penalty_payment_method' => 'cash',
                'penalty_paid_at' => '2026-06-13 18:05:00',
                'penalty_notes' => 'Denda dibayar saat return.',
            ])
            ->assertRedirect(route('rentals.show', $rental, absolute: false));

        $rental->refresh();
        $penaltyPayment = $rental->payments()->where('payment_type', 'denda')->firstOrFail();

        $this->assertSame('returned', $rental->status);
        $this->assertSame(3, $rental->penalty_days);
        $this->assertSame('75000.00', $rental->penalty_amount);
        $this->assertSame('575000.00', $rental->total_amount);
        $this->assertSame('575000.00', $rental->paid_amount);
        $this->assertSame('0.00', $rental->remaining_amount);
        $this->assertSame('paid', $rental->payment_status);
        $this->assertSame('cash', $penaltyPayment->payment_method);
        $this->assertSame('75000.00', $penaltyPayment->amount);
    }

    public function test_rental_can_be_cancelled_only_when_booked(): void
    {
        $bookedRental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'booked',
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.cancel', $bookedRental))
            ->assertRedirect(route('rentals.show', $bookedRental, absolute: false));

        $this->assertSame('cancelled', $bookedRental->refresh()->status);

        $pickedUpRental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'picked_up',
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.cancel', $pickedUpRental))
            ->assertSessionHasErrors('status');

        $this->assertSame('picked_up', $pickedUpRental->refresh()->status);
    }

    public function test_invalid_status_transitions_are_rejected(): void
    {
        $bookedRental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'booked',
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.return', $bookedRental), [
                'returned_at' => '2026-06-07 15:30:00',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame('booked', $bookedRental->refresh()->status);

        $returnedRental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'returned',
            'payment_status' => 'paid',
            'remaining_amount' => 0,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.pick-up', $returnedRental))
            ->assertSessionHasErrors('status');

        $this->assertSame('returned', $returnedRental->refresh()->status);
    }

    public function test_completed_requires_returned_and_fully_paid_rental(): void
    {
        $pickedUpRental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'picked_up',
            'payment_status' => 'paid',
            'remaining_amount' => 0,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.complete', $pickedUpRental))
            ->assertSessionHasErrors('status');

        $this->assertSame('picked_up', $pickedUpRental->refresh()->status);

        $unpaidReturnedRental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'returned',
            'payment_status' => 'dp',
            'total_amount' => 500000,
            'paid_amount' => 200000,
            'remaining_amount' => 300000,
        ]);

        $this->actingAs($this->user)
            ->post(route('rentals.complete', $unpaidReturnedRental))
            ->assertSessionHasErrors('status');

        $this->assertSame('returned', $unpaidReturnedRental->refresh()->status);
    }

    public function test_rental_detail_exposes_status_action_flags(): void
    {
        $rental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'invoice_number' => 'INV-20260607-8888',
            'status' => 'booked',
        ]);

        $this->actingAs($this->user)
            ->get(route('rentals.show', $rental))
            ->assertOk()
            ->assertSee('INV-20260607-8888')
            ->assertSee('"can_pick_up":true', false)
            ->assertSee('"can_cancel":true', false)
            ->assertSee('"can_delete":true', false);
    }

    public function test_owner_can_delete_rental_for_data_cleanup(): void
    {
        $rental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'invoice_number' => 'INV-DELETE-0001',
        ]);
        $item = RentalItem::factory()->create([
            'rental_id' => $rental->id,
        ]);
        $payment = RentalPayment::factory()->create([
            'rental_id' => $rental->id,
            'created_by' => $this->user->id,
        ]);
        $notification = RentalWhatsappNotification::query()->create([
            'rental_id' => $rental->id,
            'type' => 'return_reminder_today',
            'scheduled_for' => now(),
        ]);

        $this->actingAs($this->user)
            ->delete(route('rentals.destroy', $rental))
            ->assertRedirect(route('rentals.index', absolute: false))
            ->assertSessionHas('success');

        $this->assertModelMissing($rental);
        $this->assertDatabaseMissing('rental_items', ['id' => $item->id]);
        $this->assertDatabaseMissing('rental_payments', ['id' => $payment->id]);
        $this->assertDatabaseMissing('rental_whatsapp_notifications', ['id' => $notification->id]);
    }

    public function test_staff_can_not_delete_rental(): void
    {
        $staff = User::factory()->staff()->create();
        $rental = Rental::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($staff)
            ->delete(route('rentals.destroy', $rental))
            ->assertForbidden();

        $this->assertModelExists($rental);
    }
}
