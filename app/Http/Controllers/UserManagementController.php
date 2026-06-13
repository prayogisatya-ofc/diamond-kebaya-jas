<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreManagedUserRequest;
use App\Http\Requests\UpdateManagedUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->latest()
            ->get()
            ->map(fn (User $user): array => $this->userPayload($user));

        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Create', [
            'roles' => $this->roleOptions(),
        ]);
    }

    public function store(StoreManagedUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        User::query()->create([
            'name' => $validated['name'],
            'username' => $validated['username'] ?? null,
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Users/Edit', [
            'managedUser' => $this->userPayload($user),
            'roles' => $this->roleOptions(),
        ]);
    }

    public function update(UpdateManagedUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $isActive = (bool) ($validated['is_active'] ?? false);

        if (! $this->wouldLeaveActiveOwner($user, $validated['role'], $isActive)) {
            return back()->withErrors([
                'user' => 'Minimal harus ada satu owner aktif.',
            ]);
        }

        $user->update([
            'name' => $validated['name'],
            'username' => $validated['username'] ?? null,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => $isActive,
        ]);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role->value,
            'is_active' => $user->is_active,
            'created_at' => $user->created_at,
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return collect(UserRole::cases())
            ->map(fn (UserRole $role): array => [
                'value' => $role->value,
                'label' => str($role->value)->headline()->toString(),
            ])
            ->all();
    }

    private function wouldLeaveActiveOwner(User $user, string $role, bool $isActive): bool
    {
        if ($role === UserRole::Owner->value && $isActive) {
            return true;
        }

        return User::query()
            ->whereKeyNot($user->id)
            ->where('role', UserRole::Owner->value)
            ->where('is_active', true)
            ->exists();
    }
}
