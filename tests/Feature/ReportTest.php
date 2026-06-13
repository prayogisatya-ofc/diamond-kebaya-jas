<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\RentalPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->owner()->create(['name' => 'Owner Diamond']);
    }

    public function test_guests_are_redirected_from_reports(): void
    {
        $this->get(route('reports.transactions'))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_transaction_report_can_filter_by_status_payment_status_and_customer(): void
    {
        $customer = Customer::factory()->create(['name' => 'Maya Putri']);
        $otherCustomer = Customer::factory()->create(['name' => 'Budi Santoso']);

        Rental::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $this->user->id,
            'invoice_number' => 'INV-REPORT-0001',
            'status' => 'booked',
            'payment_status' => 'dp',
            'created_at' => '2026-06-07 10:00:00',
        ]);
        Rental::factory()->create([
            'customer_id' => $otherCustomer->id,
            'created_by' => $this->user->id,
            'invoice_number' => 'INV-REPORT-0002',
            'status' => 'completed',
            'payment_status' => 'paid',
            'created_at' => '2026-06-07 11:00:00',
        ]);

        $this->actingAs($this->user)
            ->get(route('reports.transactions', [
                'date_from' => '2026-06-07',
                'date_to' => '2026-06-07',
                'status' => 'booked',
                'payment_status' => 'dp',
                'customer_id' => $customer->id,
            ]))
            ->assertOk()
            ->assertSee('INV-REPORT-0001')
            ->assertSee('Maya Putri')
            ->assertDontSee('INV-REPORT-0002');
    }

    public function test_payment_report_can_filter_by_type_method_and_staff(): void
    {
        $staff = User::factory()->staff()->create(['name' => 'Staff Kasir']);
        $otherStaff = User::factory()->staff()->create(['name' => 'Staff Lain']);
        $rental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'invoice_number' => 'INV-PAY-0001',
        ]);

        RentalPayment::factory()->create([
            'rental_id' => $rental->id,
            'payment_type' => 'denda',
            'payment_method' => 'cash',
            'amount' => 25000,
            'paid_at' => '2026-06-07 12:00:00',
            'created_by' => $staff->id,
        ]);
        RentalPayment::factory()->create([
            'rental_id' => $rental->id,
            'payment_type' => 'dp',
            'payment_method' => 'transfer',
            'amount' => 100000,
            'paid_at' => '2026-06-07 13:00:00',
            'created_by' => $otherStaff->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('reports.payments', [
                'date_from' => '2026-06-07',
                'date_to' => '2026-06-07',
                'payment_type' => 'denda',
                'payment_method' => 'cash',
                'staff_id' => $staff->id,
            ]))
            ->assertOk()
            ->assertSee('INV-PAY-0001')
            ->assertSee('Staff Kasir')
            ->assertSee('denda')
            ->assertDontSee('"amount":"100000.00"', false);
    }

    public function test_rented_product_report_aggregates_quantity_and_revenue(): void
    {
        $product = Product::factory()->create([
            'name' => 'Kebaya Silver',
            'code' => 'KB-SLV',
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size M Silver',
            'sku' => 'KB-SLV-M',
        ]);
        $rental = Rental::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'completed',
            'created_at' => '2026-06-07 10:00:00',
        ]);

        RentalItem::factory()->create([
            'rental_id' => $rental->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'item_name_snapshot' => 'Kebaya Silver',
            'variant_name_snapshot' => 'Size M Silver',
            'quantity' => 2,
            'final_price' => 300000,
        ]);
        RentalItem::factory()->create([
            'rental_id' => $rental->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'item_name_snapshot' => 'Kebaya Silver',
            'variant_name_snapshot' => 'Size M Silver',
            'quantity' => 3,
            'final_price' => 450000,
        ]);

        $this->actingAs($this->user)
            ->get(route('reports.rented-products', [
                'date_from' => '2026-06-07',
                'date_to' => '2026-06-07',
            ]))
            ->assertOk()
            ->assertSee('Kebaya Silver')
            ->assertSee('Size M Silver')
            ->assertSee('"total_quantity":5', false)
            ->assertSee('"total_revenue":750000', false);
    }
}
