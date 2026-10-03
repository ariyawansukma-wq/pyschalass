<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceFingerprintService
{
    /**
     * Generate a deterministic 64-character SHA-256 fingerprint hash from client request headers.
     * Following panchodp/laravel-fingerprint architecture:
     * UserAgent | Accept-Language | Accept-Encoding [ | IP ]
     */
    public function generate(Request $request, bool $includeIp = false): string
    {
        $userAgent = $request->userAgent() ?: 'Unknown Agent';
        $acceptLanguage = $request->header('Accept-Language', '');
        $acceptEncoding = $request->header('Accept-Encoding', '');

        $parts = [
            $userAgent,
            $acceptLanguage,
            $acceptEncoding,
        ];

        if ($includeIp) {
            $parts[] = $request->ip();
        }

        return hash('sha256', implode('|', $parts));
    }
}
