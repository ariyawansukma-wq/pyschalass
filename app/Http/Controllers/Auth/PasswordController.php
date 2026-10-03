<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class PasswordController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/Password', [
            'authUser' => Auth::user()->only(['id', 'name', 'username', 'email', 'role', 'instansi', 'no_hp']),
        ]);
    }

    public function update(UpdatePasswordRequest $request)
    {
        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Current password is invalid.',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('status', 'Password updated successfully.');
    }

    /**
     * Update profil (nama, instansi, no_hp) — untuk kader terutama.
     */
    public function updateProfile(\Illuminate\Http\Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'instansi' => 'nullable|string|max:255',
            'no_hp'    => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return back()->with('status', 'Profil berhasil diperbarui.');
    }
}
