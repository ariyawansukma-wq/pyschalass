<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Notifications\AccountDeactivatedNotification;
use App\Services\DeviceFingerprintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/Login');
    }

    public function authenticate(LoginRequest $request)
    {
        $throttleKey = 'login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'username' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        // Cari user berdasarkan username ATAU email
        // Kader login pakai email, officer/admin pakai username
        $login   = trim($request->username);
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);

        $user = $isEmail
            ? User::where('email', $login)->first()
            : User::where('username', $login)->first();

        if ($user) {
            if (!$user->is_active) {
                return back()->withErrors([
                    'username' => 'Akun Anda dinonaktifkan. Hubungi Administrator.',
                ]);
            }

            if ($user->isExpired()) {
                if ($user->email && is_null($user->expired_notified_at)) {
                    $user->notify(new AccountDeactivatedNotification());
                    $user->update(['is_active' => false, 'expired_notified_at' => now()]);
                } else {
                    $user->update(['is_active' => false]);
                }

                return back()->withErrors([
                    'username' => 'Masa aktif akun berakhir pada ' . ($user->active_until ? $user->active_until->format('d/m/Y') : '') . '. Hubungi Administrator.',
                ]);
            }
        }

        // Guard 'web' menggunakan kolom 'username' — gunakan username dari DB
        // agar Auth::validate bekerja meski user login dengan email
        $credentials = $user
            ? ['username' => $user->username, 'password' => $request->password]
            : ['username' => $login,           'password' => $request->password];

        if (!Auth::validate($credentials)) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withErrors([
                'username' => 'Username/email atau password salah.',
            ]);
        }

        $maxDevices = max(1, (int) ($user->max_devices ?? 1));

        $fingerprintService = new DeviceFingerprintService();
        $fingerprint = $fingerprintService->generate($request);

        // Enforce maximum concurrent active devices / sessions
        if (Schema::hasTable('sessions')) {
            $previousSessionId = $request->session()->getId();
            $currentIp = $request->ip();
            $currentUserAgent = $request->userAgent();

            // Inactivity threshold (30 minutes idle timeout)
            $idleTimeoutMinutes = (int) config('session.idle_timeout', 30);
            $activeThreshold = now()->subMinutes($idleTimeoutMinutes)->timestamp;

            // 1. Purge inactive / idle sessions older than threshold
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('last_activity', '<', $activeThreshold)
                ->delete();

            // 2. If logging in from the same device (matching fingerprint or matching IP/User-Agent), purge the previous session
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $previousSessionId)
                ->where(function ($q) use ($fingerprint, $currentIp, $currentUserAgent) {
                    $q->where('fingerprint', $fingerprint);
                    if ($currentIp && $currentUserAgent) {
                        $q->orWhere(function ($sub) use ($currentIp, $currentUserAgent) {
                            $sub->where('ip_address', $currentIp)
                                ->where('user_agent', $currentUserAgent);
                        });
                    }
                })
                ->delete();

            // 3. Count remaining genuinely active sessions from other devices (different fingerprints)
            $activeOtherSessionsCount = DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $previousSessionId)
                ->where('last_activity', '>=', $activeThreshold)
                ->where(function ($q) use ($fingerprint) {
                    $q->where('fingerprint', '!=', $fingerprint)
                      ->orWhereNull('fingerprint');
                })
                ->count();

            // 4. If maximum devices reached, reject login until another device logs out or expires
            if ($activeOtherSessionsCount >= $maxDevices) {
                $deviceWord = $maxDevices > 1 ? 'devices' : 'device';
                return back()->withErrors([
                    'username' => "Batas perangkat aktif tercapai ({$maxDevices} {$deviceWord}). Keluar dari perangkat lain terlebih dahulu.",
                ]);
            }
        }

        Auth::login($user);
        $request->session()->regenerate();
        RateLimiter::clear($throttleKey);

        if (Schema::hasTable('sessions')) {
            $newSessionId = $request->session()->getId();
            DB::table('sessions')->updateOrInsert(
                ['id' => $newSessionId],
                [
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 500),
                    'payload' => base64_encode(serialize($request->session()->all())),
                    'last_activity' => now()->timestamp,
                    'fingerprint' => $fingerprint,
                ]
            );
        }

        $request->session()->put('device_fingerprint', $fingerprint);

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
