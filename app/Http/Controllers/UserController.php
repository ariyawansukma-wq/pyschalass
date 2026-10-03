<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->input('per_page', 10), 5), 100);

        $query = QueryBuilder::for(User::class)
            ->allowedSorts('name', 'username', 'role', 'created_at')
            ->defaultSort('-created_at');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($perPage)->onEachSide(1)->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return redirect()->route('users.index');
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        User::create([
            'name'         => $validated['name'],
            'username'     => $validated['username'],
            'email'        => $validated['email'] ?? null,
            'password'     => Hash::make($validated['password']),
            'role'         => $validated['role'],
            'instansi'     => $request->instansi ?: null,
            'no_hp'        => $request->no_hp ?: null,
            'max_devices'  => (int) ($request->input('max_devices', 1) ?: 1),
            'active_until' => $request->active_until ?: null,
            'is_active'    => $request->boolean('is_active', true),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User account created successfully.');
    }

    public function edit(User $user)
    {
        return redirect()->route('users.index');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = [
            'username'     => $request->username,
            'email'        => $request->email,
            'name'         => $request->name,
            'role'         => $request->role,
            'instansi'     => $request->instansi ?: null,
            'no_hp'        => $request->no_hp ?: null,
            'max_devices'  => (int) ($request->input('max_devices', 1) ?: 1),
            'active_until' => $request->active_until ?: null,
        ];

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $newActiveUntil = $request->active_until ?: null;
        if ($newActiveUntil) {
            $parsedDate = Carbon::parse($newActiveUntil);
            if ($parsedDate->endOfDay()->isFuture()) {
                $data['expired_notified_at'] = null;
            }
        } elseif ($request->has('is_active') && $request->boolean('is_active')) {
            $data['expired_notified_at'] = null;
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function resetPassword(ResetPasswordRequest $request, User $user)
    {
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password reset successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->username === 'admin') {
            return back()->with('error', 'Default admin user cannot be deleted.');
        }

        if (auth()->id() === $user->id) {
            return back()->with('error', 'Tidak dapat menghapus user yang sedang digunakan.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function toggleStatus(User $user)
    {
        if ($user->username === 'admin') {
            return back()->with('error', 'Cannot disable the default admin account.');
        }

        if (auth()->id() === $user->id) {
            return back()->with('error', 'Cannot disable your own account.');
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User account has been {$status} successfully.");
    }

    /**
     * Send password reset link to a specific user.
     */
    public function sendResetLink(Request $request, User $user)
    {
        if ($request->has('email')) {
            $request->validate([
                'email' => 'required|email|unique:users,email,' . $user->id,
            ]);
            $user->update(['email' => $request->email]);
        }

        if (!$user->email) {
            return back()->with('error', 'User does not have an email address set.');
        }

        $status = Password::broker()->sendResetLink(
            ['email' => $user->email]
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', 'Password reset email sent successfully.')
            : back()->with('error', __($status));
    }
}
