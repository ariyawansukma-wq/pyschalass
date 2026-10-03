<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\SportBranchController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\AthleteController;
use App\Http\Controllers\BenchmarkController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\SignatoryController;
use App\Http\Controllers\LetterheadTemplateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Select2Controller;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\ComparisonController;
use App\Http\Controllers\CameraAssessmentController;
use App\Http\Controllers\GeneratedReportController;
use App\Models\SportBranch;
use App\Models\Athlete;
use App\Models\Folder;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'show'])->middleware('guest')->name('register');
Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'store'])->middleware('guest');

Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:3,1')->name('password.email');
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
Route::post('/reset-password', [ResetPasswordController::class, 'store'])->middleware('throttle:3,1')->name('password.store');

Route::middleware(['auth', 'fingerprint'])->group(function () {
    Route::post('/heartbeat', fn() => response()->json(['status' => 'alive']))->name('heartbeat');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/password', [PasswordController::class, 'show'])->name('password.show');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');
    Route::put('/profile', [PasswordController::class, 'updateProfile'])->name('profile.update');

    Route::middleware('can:manage-comparison')->group(function () {
        Route::get('comparison', [ComparisonController::class, 'index'])->name('comparison.index');
        Route::get('athletes/{athlete}/comparison', [AthleteController::class, 'comparison'])->name('athletes.comparison');
    });

    Route::middleware('can:manage-sport-branches')->group(function () {
        Route::post('sport-branches/{sport_branch}/duplicate', [SportBranchController::class, 'duplicate'])->name('sport-branches.duplicate');
        Route::put('sport-branches/{sport_branch}/reorder-indicators', [SportBranchController::class, 'reorderIndicators'])->name('sport-branches.reorder-indicators');
        Route::resource('sport-branches', SportBranchController::class)->except(['create', 'edit']);
        Route::post('indicators/{id}/restore', [IndicatorController::class, 'restore'])->name('indicators.restore');
        Route::delete('indicators/{id}/force-delete', [IndicatorController::class, 'forceDelete'])->name('indicators.force-delete');
        Route::resource('indicators', IndicatorController::class)->only(['store', 'update', 'destroy']);
    });

    Route::middleware('can:manage-benchmarks')->group(function () {
        Route::get('benchmarks/download-template', [BenchmarkController::class, 'downloadTemplate'])->name('benchmarks.download-template');
        Route::post('benchmarks/import', [BenchmarkController::class, 'import'])->name('benchmarks.import');
        Route::resource('benchmarks', BenchmarkController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::middleware('can:view-folders')->group(function () {
        Route::get('folders', [FolderController::class, 'index'])->name('folders.index');
        Route::get('folders/{folder}', [FolderController::class, 'show'])->name('folders.show');
        Route::get('folders/{folder}/export-pdf', [FolderController::class, 'exportPdf'])->name('folders.export-pdf');
    });

    Route::middleware('can:manage-folders')->group(function () {
        Route::post('folders', [FolderController::class, 'store'])->name('folders.store');
        Route::delete('folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');
        Route::post('api/folders', [FolderController::class, 'apiStore'])->name('api.folders.store');
    });

    Route::middleware('can:manage-reports')->group(function () {
        Route::get('final-report', [ReportController::class, 'finalReportForm'])->name('final-report.form');
        Route::post('final-report/preview', [ReportController::class, 'generateFinalReport'])->name('final-report.generate');
    });

    // Export Center & Generated Reports
    Route::get('exports', [GeneratedReportController::class, 'index'])->name('exports.index');
    Route::get('exports/{generatedReport}/download', [GeneratedReportController::class, 'download'])->name('exports.download');
    Route::delete('exports/{generatedReport}', [GeneratedReportController::class, 'destroy'])->name('exports.destroy');
    Route::post('api/generated-reports', [GeneratedReportController::class, 'store'])->name('api.generated-reports.store');
    Route::post('api/generated-reports/queue', [GeneratedReportController::class, 'dispatchQueueJob'])->name('api.generated-reports.queue');

    Route::middleware('can:manage-settings')->group(function () {
        Route::resource('institutions', InstitutionController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('institutions/{institution}/logos', [InstitutionController::class, 'storeLogo'])->name('institutions.logos.store');
        Route::delete('institutions/{institution}/logos/{logo}', [InstitutionController::class, 'destroyLogo'])->name('institutions.logos.destroy');
        Route::patch('institutions/{institution}/logos/{logo}', [InstitutionController::class, 'updateLogoHeight'])->name('institutions.logos.update-height');
        Route::post('institutions/{institution}/signatories', [SignatoryController::class, 'store'])->name('signatories.store');
        Route::put('institutions/{institution}/signatories/{signatory}', [SignatoryController::class, 'update'])->name('signatories.update');
        Route::delete('institutions/{institution}/signatories/{signatory}', [SignatoryController::class, 'destroy'])->name('signatories.destroy');
        Route::post('institutions/{institution}/letterhead-templates', [LetterheadTemplateController::class, 'store'])->name('letterhead-templates.store');
        Route::put('institutions/{institution}/letterhead-templates/{letterheadTemplate}', [LetterheadTemplateController::class, 'update'])->name('letterhead-templates.update');
        Route::delete('institutions/{institution}/letterhead-templates/{letterheadTemplate}', [LetterheadTemplateController::class, 'destroy'])->name('letterhead-templates.destroy');
    });

    Route::middleware('can:delete-athletes')->group(function () {
        Route::delete('athletes/delete-all', [AthleteController::class, 'destroyAll'])->name('athletes.destroy-all');
        Route::delete('athletes/{athlete}', [AthleteController::class, 'destroy'])->name('athletes.destroy');
    });

    Route::post('athletes/{athlete}/save-trial', [AthleteController::class, 'saveTrial'])->name('athletes.save-trial');
    Route::post('athletes/{athlete}/save-anthropometry', [AthleteController::class, 'saveAnthropometry'])->name('athletes.save-anthropometry');
    Route::get('athletes/export/pdf', [AthleteController::class, 'exportDirectoryPdf'])->name('athletes.export-directory-pdf');
    Route::get('athletes/export/excel', [AthleteController::class, 'exportDirectoryExcel'])->name('athletes.export-directory-excel');
    Route::match(['get', 'post'], 'athletes/{athlete}/export-pdf', [AthleteController::class, 'exportPdf'])->name('athletes.export-pdf');
    Route::get('athletes/{athlete}/export-excel', [AthleteController::class, 'exportExcel'])->name('athletes.export-excel');
    Route::resource('athletes', AthleteController::class)->except(['show', 'destroy']);

    Route::middleware('can:manage-users')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('users/{user}/send-reset-link', [UserController::class, 'sendResetLink'])->name('users.send-reset-link');
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    });

    // Camera Assessment (physical assessment via camera)
    Route::get('physical-assessment', [CameraAssessmentController::class, 'session'])->name('physical-assessment.index');
    Route::get('physical-assessment/{athlete}/{testId}', [CameraAssessmentController::class, 'start'])->name('physical-assessment.start');
    Route::get('camera-assessments', [CameraAssessmentController::class, 'index'])->name('camera-assessments.index');
    Route::post('camera-assessments', [CameraAssessmentController::class, 'store'])->name('camera-assessments.store');
    Route::get('camera-assessments/{cameraAssessment}', [CameraAssessmentController::class, 'show'])->name('camera-assessments.show');
    Route::delete('camera-assessments/{cameraAssessment}', [CameraAssessmentController::class, 'destroy'])->name('camera-assessments.destroy');

    // Screening Anak ISKAD (kader)
    Route::get('screening', [App\Http\Controllers\ScreeningController::class, 'index'])->name('screening.index');
    Route::get('screening/mulai', [App\Http\Controllers\ScreeningController::class, 'create'])->name('screening.create');
    Route::post('screening', [App\Http\Controllers\ScreeningController::class, 'store'])->name('screening.store');
    Route::get('screening/{screening}', [App\Http\Controllers\ScreeningController::class, 'show'])->name('screening.show');
    Route::delete('screening/{screening}', [App\Http\Controllers\ScreeningController::class, 'destroy'])->name('screening.destroy');

    // Perpustakaan / Karya Ilmiah
    Route::get('perpustakaan', [App\Http\Controllers\LibraryController::class, 'index'])->name('library.index');
    Route::post('perpustakaan', [App\Http\Controllers\LibraryController::class, 'store'])->name('library.store');
    Route::delete('perpustakaan/{karyaIlmiah}', [App\Http\Controllers\LibraryController::class, 'destroy'])->name('library.destroy');

    // Select2 & API Helpers
    Route::get('api/sport-branches/{id}/details', [SportBranchController::class, 'getDetails'])->name('api.sport-branches.details');
    Route::get('api/select2/sport-branches', [Select2Controller::class, 'sportBranches'])->name('api.select2.sport-branches');
    Route::get('api/select2/folders', [Select2Controller::class, 'folders'])->name('api.select2.folders');
    Route::get('api/select2/institutions', [Select2Controller::class, 'institutions'])->name('api.select2.institutions');
    Route::get('api/select2/athletes', [Select2Controller::class, 'athletes'])->name('api.select2.athletes');


});
