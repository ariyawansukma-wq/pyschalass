<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class RegisterController extends Controller
{
    /**
     * Halaman registrasi kader.
     * Hanya bisa diakses saat belum login (guest).
     */
    public function show()
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Proses registrasi kader baru.
     * Role selalu 'kader' — tidak bisa memilih admin/officer sendiri.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'instansi' => 'required|string|max:255',
            'no_hp'    => 'required|string|max:20',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers(),
            ],
        ], [
            'name.required'     => 'Nama lengkap wajib diisi.',
            'email.required'    => 'Email wajib diisi.',
            'email.unique'      => 'Email sudah digunakan. Coba email lain.',
            'instansi.required' => 'Nama instansi wajib diisi.',
            'no_hp.required'    => 'Nomor HP wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed'=> 'Konfirmasi password tidak cocok.',
        ]);

        // Generate username dari email (bagian sebelum @) + angka acak jika sudah ada
        $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $validated['email'])[0]));
        $username     = $baseUsername;
        $counter      = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter++;
        }

        $user = User::create([
            'name'     => $validated['name'],
            'username' => $username,
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => 'kader',
            'instansi' => $validated['instansi'],
            'no_hp'    => $validated['no_hp'],
            'is_active'=> true,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Akun kader berhasil dibuat. Selamat datang, ' . $user->name . '!');
    }
}
