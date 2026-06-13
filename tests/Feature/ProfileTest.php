<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_authenticated_user_can_view_profile_page(): void
    {
        $user = User::factory()->staff()->create([
            'name' => 'Staff Profil',
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Staff Profil');
    }

    public function test_authenticated_user_can_update_own_profile(): void
    {
        $user = User::factory()->staff()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Nama Baru',
                'username' => 'nama_baru',
                'email' => 'nama.baru@example.test',
            ])
            ->assertRedirect(route('profile.edit', absolute: false));

        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('nama_baru', $user->username);
        $this->assertSame('nama.baru@example.test', $user->email);
    }

    public function test_profile_email_and_username_must_be_unique(): void
    {
        $otherUser = User::factory()->create([
            'username' => 'dipakai',
            'email' => 'dipakai@example.test',
        ]);
        $user = User::factory()->staff()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => 'Nama Baru',
                'username' => $otherUser->username,
                'email' => $otherUser->email,
            ])
            ->assertRedirect(route('profile.edit', absolute: false))
            ->assertSessionHasErrors(['username', 'email']);
    }

    public function test_user_can_update_password_with_current_password(): void
    {
        $user = User::factory()->staff()->create();

        $this->actingAs($user)
            ->put(route('profile.password.update'), [
                'current_password' => 'password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect(route('profile.edit', absolute: false));

        $this->assertTrue(Hash::check('new-secure-password', $user->refresh()->password));
    }

    public function test_password_update_requires_current_password(): void
    {
        $user = User::factory()->staff()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect(route('profile.edit', absolute: false))
            ->assertSessionHasErrors('current_password');
    }
}
