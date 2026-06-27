<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_can_view_expenses_index(): void
    {
        $owner = User::factory()->owner()->create();
        Expense::factory()->for($owner, 'creator')->count(3)->create();

        $this->actingAs($owner)
            ->get(route('expenses.index'))
            ->assertOk();
    }

    public function test_staff_can_view_expenses_index(): void
    {
        $staff = User::factory()->staff()->create();
        Expense::factory()->for($staff, 'creator')->count(2)->create();

        $this->actingAs($staff)
            ->get(route('expenses.index'))
            ->assertOk();
    }

    public function test_owner_can_create_expense(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->post(route('expenses.store'), [
                'description' => 'Bayar listrik bulan Juni',
                'amount' => 150000,
                'category' => 'operasional',
                'expense_date' => '2026-06-27',
                'notes' => 'Tagihan rutin',
            ])
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'description' => 'Bayar listrik bulan Juni',
            'amount' => 150000,
            'category' => 'operasional',
            'created_by' => $owner->id,
        ]);
    }

    public function test_staff_can_create_expense(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->post(route('expenses.store'), [
                'description' => 'Beli deterjen',
                'amount' => 50000,
                'category' => 'laundry',
                'expense_date' => '2026-06-26',
            ])
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'description' => 'Beli deterjen',
            'created_by' => $staff->id,
        ]);
    }

    public function test_user_can_update_expense(): void
    {
        $owner = User::factory()->owner()->create();
        $expense = Expense::factory()->for($owner, 'creator')->create([
            'description' => 'Old description',
            'amount' => 100000,
        ]);

        $this->actingAs($owner)
            ->put(route('expenses.update', $expense), [
                'description' => 'Updated description',
                'amount' => 200000,
                'category' => 'maintenance',
                'expense_date' => '2026-06-27',
            ])
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'description' => 'Updated description',
            'amount' => 200000,
        ]);
    }

    public function test_user_can_delete_expense(): void
    {
        $owner = User::factory()->owner()->create();
        $expense = Expense::factory()->for($owner, 'creator')->create();

        $this->actingAs($owner)
            ->delete(route('expenses.destroy', $expense))
            ->assertRedirect(route('expenses.index'));

        $this->assertModelMissing($expense);
    }

    public function test_expense_requires_description(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->post(route('expenses.store'), [
                'amount' => 50000,
                'category' => 'operasional',
                'expense_date' => '2026-06-27',
            ])
            ->assertSessionHasErrors('description');
    }

    public function test_expense_requires_positive_amount(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->post(route('expenses.store'), [
                'description' => 'Test',
                'amount' => 0,
                'category' => 'operasional',
                'expense_date' => '2026-06-27',
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_expense_requires_valid_category(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->post(route('expenses.store'), [
                'description' => 'Test',
                'amount' => 50000,
                'category' => 'invalid_category',
                'expense_date' => '2026-06-27',
            ])
            ->assertSessionHasErrors('category');
    }

    public function test_guest_cannot_access_expenses(): void
    {
        $this->get(route('expenses.index'))
            ->assertRedirect(route('login'));
    }

    public function test_expenses_can_be_filtered_by_category(): void
    {
        $owner = User::factory()->owner()->create();
        Expense::factory()->for($owner, 'creator')->create(['category' => 'operasional']);
        Expense::factory()->for($owner, 'creator')->create(['category' => 'laundry']);
        Expense::factory()->for($owner, 'creator')->create(['category' => 'laundry']);

        $this->actingAs($owner)
            ->get(route('expenses.index', ['category' => 'laundry']))
            ->assertOk();
    }
}
