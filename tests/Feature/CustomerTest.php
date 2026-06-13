<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->owner()->create();
    }

    public function test_guest_is_redirected_from_customers(): void
    {
        $response = $this->get(route('customers.index'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_customers_can_be_managed(): void
    {
        $this->actingAs($this->user)
            ->post(route('customers.store'), [
                'name' => 'Siti Aminah',
                'whatsapp_number' => '081234567890',
                'notes' => 'Repeat order keluarga.',
            ])
            ->assertRedirect();

        $customer = Customer::query()->where('whatsapp_number', '081234567890')->firstOrFail();

        $this->assertModelExists($customer);
        $this->assertSame('Siti Aminah', $customer->name);

        $this->actingAs($this->user)
            ->put(route('customers.update', $customer), [
                'name' => 'Siti Aminah Update',
                'whatsapp_number' => '081111222333',
                'notes' => null,
            ])
            ->assertRedirect(route('customers.show', $customer, absolute: false));

        $customer->refresh();

        $this->assertSame('Siti Aminah Update', $customer->name);
        $this->assertSame('081111222333', $customer->whatsapp_number);
        $this->assertNull($customer->notes);

        $this->actingAs($this->user)
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index', absolute: false));

        $this->assertModelMissing($customer);
    }

    public function test_customers_can_be_searched_by_name_or_whatsapp_number(): void
    {
        Customer::factory()->create([
            'name' => 'Budi Santoso',
            'whatsapp_number' => '081900001111',
        ]);

        Customer::factory()->create([
            'name' => 'Dewi Lestari',
            'whatsapp_number' => '082200002222',
        ]);

        $this->actingAs($this->user)
            ->get(route('customers.index', ['search' => 'Budi']))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Dewi Lestari');

        $this->actingAs($this->user)
            ->get(route('customers.index', ['search' => '2222']))
            ->assertOk()
            ->assertSee('Dewi Lestari')
            ->assertDontSee('Budi Santoso');
    }

    public function test_customer_detail_is_safe_when_customer_has_no_rentals(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Rina Kartika',
            'whatsapp_number' => '089876543210',
        ]);

        $this->actingAs($this->user)
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Rina Kartika')
            ->assertSee('"rentalHistory":[]', false)
            ->assertSee('"hasRentalHistory":true', false);
    }

    public function test_customer_validation_requires_name_and_whatsapp_number(): void
    {
        $this->actingAs($this->user)
            ->post(route('customers.store'), [
                'name' => '',
                'whatsapp_number' => '',
                'notes' => null,
            ])
            ->assertSessionHasErrors(['name', 'whatsapp_number']);
    }
}
