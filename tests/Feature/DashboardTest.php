<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Rental;
use App\Models\RentalPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_authenticated_users_can_view_dashboard(): void
    {
        $user = User::factory()->owner()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard');
    }

    public function test_dashboard_displays_operational_metrics(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-07 10:00:00'));

        $user = User::factory()->owner()->create();
        $customer = Customer::factory()->create(['name' => 'Sinta Lestari']);
        $rental = Rental::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $user->id,
            'invoice_number' => 'INV-20260607-9001',
            'status' => 'booked',
            'payment_status' => 'dp',
            'pickup_at' => '2026-06-07 13:00:00',
            'return_due_at' => '2026-06-09 17:00:00',
            'total_amount' => 500000,
            'paid_amount' => 200000,
            'remaining_amount' => 300000,
        ]);
        Rental::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $user->id,
            'invoice_number' => 'INV-20260607-9002',
            'status' => 'picked_up',
            'return_due_at' => '2026-06-06 17:00:00',
            'remaining_amount' => 0,
        ]);
        RentalPayment::factory()->create([
            'rental_id' => $rental->id,
            'payment_type' => 'dp',
            'payment_method' => 'cash',
            'amount' => 200000,
            'paid_at' => '2026-06-07 09:00:00',
            'created_by' => $user->id,
        ]);
        RentalPayment::factory()->create([
            'rental_id' => $rental->id,
            'payment_type' => 'denda',
            'payment_method' => 'cash',
            'amount' => 25000,
            'paid_at' => '2026-06-07 09:30:00',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('INV-20260607-9001');
        $response->assertSee('Sinta Lestari');
        $response->assertSee('"revenue_today":225000', false);
        $response->assertSee('"outstanding_total":300000', false);
        $response->assertSee('"active_transactions":2', false);

        Carbon::setTestNow();
    }
}
