<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetManagedUserPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UserPasswordController extends Controller
{
    public function __invoke(ResetManagedUserPasswordRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->update([
            'password' => $validated['password'],
        ]);

        return redirect()->route('users.edit', $user)->with('success', 'Password user berhasil direset.');
    }
}
