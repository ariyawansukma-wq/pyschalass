<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\Trial;
use App\Observers\TrialObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

use App\Models\Anthropometry;
use App\Models\Athlete;
use App\Models\Benchmark;
use App\Models\Folder;
use App\Models\Indicator;
use App\Models\SportBranch;
use App\Services\ReportService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Trial::observe(TrialObserver::class);

        $clearReportCache = function () {
            ReportService::flushReportCache();
            \App\Services\DashboardService::flushDashboardCache();
        };

        Trial::saved($clearReportCache);
        Trial::deleted($clearReportCache);
        Anthropometry::saved($clearReportCache);
        Anthropometry::deleted($clearReportCache);
        Athlete::saved($clearReportCache);
        Athlete::deleted($clearReportCache);
        Folder::saved($clearReportCache);
        Folder::deleted($clearReportCache);
        Benchmark::saved($clearReportCache);
        Benchmark::deleted($clearReportCache);
        SportBranch::saved($clearReportCache);
        SportBranch::deleted($clearReportCache);
        Indicator::saved($clearReportCache);
        Indicator::deleted($clearReportCache);

        Gate::before(function ($user, $ability) {
            if ($user->role === UserRole::Admin->value) {
                return true;
            }
        });

        Gate::define('manage-users', function ($user) {
            return $user->role === UserRole::Admin->value;
        });

        Gate::define('delete-athletes', function ($user) {
            return in_array($user->role, [UserRole::Admin->value, UserRole::Officer->value]);
        });

        Gate::define('manage-athletes', function ($user) {
            return in_array($user->role, [UserRole::Admin->value, UserRole::Officer->value]);
        });

        Gate::define('manage-activities', function ($user) {
            return $user->role === UserRole::Admin->value;
        });

        Gate::define('manage-settings', function ($user) {
            return $user->role === UserRole::Admin->value;
        });

        Gate::define('manage-sport-branches', function ($user) {
            return in_array($user->role, [UserRole::Admin->value, UserRole::Officer->value]);
        });

        Gate::define('manage-benchmarks', function ($user) {
            return in_array($user->role, [UserRole::Admin->value, UserRole::Officer->value]);
        });

        Gate::define('manage-reports', function ($user) {
            return $user->role === UserRole::Admin->value;
        });

        Gate::define('view-folders', function ($user) {
            return in_array($user->role, [UserRole::Admin->value, UserRole::Officer->value]);
        });

        Gate::define('manage-folders', function ($user) {
            return $user->role === UserRole::Admin->value;
        });

        Gate::define('manage-comparison', function ($user) {
            return in_array($user->role, [UserRole::Admin->value, UserRole::Officer->value]);
        });

        Gate::define('manage-camera-assessments', function ($user) {
            return in_array($user->role, [UserRole::Admin->value, UserRole::Officer->value]);
        });

        // ── Screening (kader) ─────────────────────────────────────────────────────
        Gate::define('manage-screening', function ($user) {
            return in_array($user->role, [UserRole::Admin->value, UserRole::Kader->value]);
        });

        // ── Perpustakaan / Karya Ilmiah ───────────────────────────────────────────
        // Semua role yang login bisa baca. Hanya admin yang bisa tambah/hapus.
        Gate::define('view-library', function ($user) {
            return in_array($user->role, [
                UserRole::Admin->value,
                UserRole::Officer->value,
                UserRole::Kader->value,
            ]);
        });

        Gate::define('manage-library', function ($user) {
            return $user->role === UserRole::Admin->value;
        });
    }
}
