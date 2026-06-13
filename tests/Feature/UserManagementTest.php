<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_can_view_user_list(): void
    {
        $owner = User::factory()->owner()->create(['name' => 'Owner UAT']);
        User::factory()->staff()->create(['name' => 'Staff UAT']);

        $this->actingAs($owner)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Owner UAT')
            ->assertSee('Staff UAT');
    }

    public function test_staff_cannot_access_user_management(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(route('users.store'), [])
            ->assertForbidden();
    }

    public function test_owner_can_create_user(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->post(route('users.store'), [
                'name' => 'Staff Baru',
                'username' => 'staff-baru',
                'email' => 'staff-baru@diamond.test',
                'role' => 'staff',
                'is_active' => true,
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertRedirect(route('users.index', absolute: false));

        $user = User::query()->where('email', 'staff-baru@diamond.test')->firstOrFail();

        $this->assertSame('Staff Baru', $user->name);
        $this->assertSame('staff-baru', $user->username);
        $this->assertSame(UserRole::Staff, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('password-baru', $user->password));
    }

    public function test_owner_can_update_user_profile_role_and_status(): void
    {
        $owner = User::factory()->owner()->create();
        $staff = User::factory()->staff()->create([
            'name' => 'Staff Lama',
            'username' => 'staff-lama',
            'email' => 'staff-lama@diamond.test',
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->put(route('users.update', $staff), [
                'name' => 'Owner Baru',
                'username' => 'owner-baru',
                'email' => 'owner-baru@diamond.test',
                'role' => 'owner',
                'is_active' => false,
            ])
            ->assertRedirect(route('users.index', absolute: false));

        $staff->refresh();

        $this->assertSame('Owner Baru', $staff->name);
        $this->assertSame('owner-baru', $staff->username);
        $this->assertSame('owner-baru@diamond.test', $staff->email);
        $this->assertSame(UserRole::Owner, $staff->role);
        $this->assertFalse($staff->is_active);
    }

    public function test_owner_can_reset_user_password(): void
    {
        $owner = User::factory()->owner()->create();
        $staff = User::factory()->staff()->create();

        $this->actingAs($owner)
            ->post(route('users.password.update', $staff), [
                'password' => 'password-reset',
                'password_confirmation' => 'password-reset',
            ])
            ->assertRedirect(route('users.edit', $staff, absolute: false));

        $this->assertTrue(Hash::check('password-reset', $staff->refresh()->password));
    }

    public function test_last_active_owner_cannot_be_deactivated_or_demoted(): void
    {
        $owner = User::factory()->owner()->create([
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->put(route('users.update', $owner), [
                'name' => $owner->name,
                'username' => $owner->username,
                'email' => $owner->email,
                'role' => 'owner',
                'is_active' => false,
            ])
            ->assertSessionHasErrors('user');

        $this->assertTrue($owner->refresh()->is_active);
        $this->assertSame(UserRole::Owner, $owner->role);

        $this->actingAs($owner)
            ->put(route('users.update', $owner), [
                'name' => $owner->name,
                'username' => $owner->username,
                'email' => $owner->email,
                'role' => 'staff',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('user');

        $this->assertSame(UserRole::Owner, $owner->refresh()->role);
    }
}
