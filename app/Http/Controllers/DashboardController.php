<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $dashboardService)
    {
        $kpi = $dashboardService->getKpiData();
        $performanceBySport = $dashboardService->getPerformanceBySport();
        $performanceByGender = $dashboardService->getPerformanceByGender();
        $athletesBySport = $dashboardService->getAthletesBySport();
        $performanceByAgeGroup = $dashboardService->getPerformanceByAgeGroup();

        return Inertia::render('Dashboard', compact(
            'kpi',
            'performanceBySport',
            'performanceByGender',
            'athletesBySport',
            'performanceByAgeGroup'
        ));
    }
}
