<?php

namespace App\Http\Middleware;

use App\Services\DeviceFingerprintService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class FingerprintMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $enabled = (bool) config('laravel_fingerprint.enabled', true);
        if (!$enabled) {
            return $next($request);
        }

        $fingerprintService = new DeviceFingerprintService();
        $includeIp = (bool) config('laravel_fingerprint.include_ip', false);
        $currentFingerprint = $fingerprintService->generate($request, $includeIp);

        $sessionFingerprint = $request->session()->get('device_fingerprint');

        if (!$sessionFingerprint) {
            $request->session()->put('device_fingerprint', $currentFingerprint);
        } elseif ($sessionFingerprint !== $currentFingerprint) {
            // Fingerprint mismatch indicates possible session theft across different browsers/devices
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $redirectRoute = config('laravel_fingerprint.redirect_route', 'login');
            return redirect()->route($redirectRoute)->withErrors([
                'username' => 'Session invalidated due to client environment mismatch.',
            ]);
        }

        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'fingerprint')) {
            $sessionId = $request->session()->getId();
            if ($sessionId) {
                DB::table('sessions')
                    ->where('id', $sessionId)
                    ->where(function ($q) use ($currentFingerprint) {
                        $q->whereNull('fingerprint')
                          ->orWhere('fingerprint', '!=', $currentFingerprint);
                    })
                    ->update(['fingerprint' => $currentFingerprint]);
            }
        }

        return $next($request);
    }
}
