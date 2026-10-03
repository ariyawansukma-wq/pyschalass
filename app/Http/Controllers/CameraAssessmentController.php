<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\CameraAssessment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CameraAssessmentController extends Controller
{
    /**
     * Daftar definisi tes yang tersedia untuk Physical Assessment via kamera.
     * Di-hardcode karena tidak ada model Test di database.
     * name harus EXACT match dengan string comparison di AssessmentSession.vue.
     */
    public static function testDefinitions(): array
    {
        return [
            ['id' => 1, 'name' => 'Keseimbangan Statis', 'icon' => '🧍', 'category' => 'Balance',      'unit' => 'detik'],
            ['id' => 2, 'name' => 'Elbow Plank',         'icon' => '🏋️', 'category' => 'Endurance',    'unit' => 'detik'],
            ['id' => 3, 'name' => 'Wall Sit',            'icon' => '🪑', 'category' => 'Strength',     'unit' => 'detik'],
            ['id' => 4, 'name' => 'Deep Squat',          'icon' => '🦵', 'category' => 'Mobility',     'unit' => 'repetisi'],
            ['id' => 5, 'name' => 'Squat Jump',          'icon' => '⬆️', 'category' => 'Power',        'unit' => 'repetisi'],
            ['id' => 6, 'name' => 'Push Up',             'icon' => '💪', 'category' => 'Strength',     'unit' => 'repetisi'],
            ['id' => 7, 'name' => 'Sit Up',              'icon' => '🤸', 'category' => 'Strength',     'unit' => 'repetisi'],
            ['id' => 8, 'name' => 'Sit and Reach',       'icon' => '🙆', 'category' => 'Flexibility',  'unit' => 'cm'],
        ];
    }

    // ── session (physical-assessment index) ───────────────────────────────────

    /**
     * Halaman utama Physical Assessment.
     * Menampilkan pilihan athlete dan pilihan tes.
     * Dipanggil dari route GET /physical-assessment (sidebar link).
     */
    public function session(Request $request)
    {
        $user      = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;

        $athleteQuery = Athlete::select('id', 'name', 'athlete_number', 'sport_branch_id', 'user_id')
            ->with('sportBranch:id,name')
            ->orderBy('name');

        if ($isOfficer) {
            $athleteQuery->where('user_id', $user->id);
        }

        $athletes = $athleteQuery->get();

        return Inertia::render('PhysicalAssessment/Index', [
            'athletes'    => $athletes,
            'testOptions' => self::testDefinitions(),
        ]);
    }

    // ── start (assessment session untuk athlete + test tertentu) ──────────────

    /**
     * Render halaman AssessmentSession untuk athlete dan test yang dipilih.
     * Dipanggil dari route GET /physical-assessment/{athlete}/{testId}.
     *
     * Authorization: Officer hanya bisa assess athlete miliknya.
     */
    public function start(Athlete $athlete, int $testId)
    {
        $user      = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;

        if ($isOfficer && $athlete->user_id !== $user->id) {
            abort(403, 'Unauthorized: you can only assess your own athletes.');
        }

        $tests      = self::testDefinitions();
        $testDef    = collect($tests)->firstWhere('id', $testId);

        if (! $testDef) {
            abort(404, 'Test definition not found.');
        }

        return Inertia::render('PhysicalAssessment/AssessmentSession', [
            'athlete' => [
                'id'             => $athlete->id,
                'name'           => $athlete->name,
                'athlete_number' => $athlete->athlete_number,
            ],
            'test' => $testDef,
        ]);
    }

    // ── index ─────────────────────────────────────────────────────────────────

    /**
     * Tampilkan daftar camera assessment.
     *
     * Admin  : semua assessment dari semua athlete.
     * Officer: hanya assessment milik athlete dengan user_id === auth()->id().
     */
    public function index(Request $request)
    {
        $user      = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;

        $query = CameraAssessment::with([
                'athlete:id,name,athlete_number,user_id',
                'user:id,name',
            ])
            ->orderBy('performed_at', 'desc');

        if ($isOfficer) {
            // Batasi ke assessment yang athlete-nya dimiliki officer ini
            $query->whereHas('athlete', fn ($q) => $q->where('user_id', $user->id));
        }

        // Filter opsional: jenis tes
        if ($testType = $request->input('test_type')) {
            $query->where('test_type', $testType);
        }

        // Filter opsional: athlete tertentu
        if ($athleteId = $request->input('athlete_id')) {
            $query->where('athlete_id', $athleteId);
        }

        // Filter opsional: kategori
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        $perPage      = min(max((int) $request->input('per_page', 15), 5), 100);
        $assessments  = $query->paginate($perPage)->onEachSide(1)->withQueryString();

        // Daftar athlete untuk dropdown filter — dibatasi sesuai role
        $athleteQuery = Athlete::select('id', 'name', 'athlete_number')->orderBy('name');
        if ($isOfficer) {
            $athleteQuery->where('user_id', $user->id);
        }
        $athletes = $athleteQuery->get();

        return Inertia::render('PhysicalAssessment/History', [
            'assessments' => $assessments,
            'athletes'    => $athletes,
            'filters'     => $request->only(['test_type', 'athlete_id', 'category', 'per_page']),
        ]);
    }

    // ── store ─────────────────────────────────────────────────────────────────

    /**
     * Simpan hasil assessment dari physical-assessment.
     *
     * user_id SELALU diambil dari auth()->id() — tidak pernah dari request.
     *
     * Authorization:
     *   Officer hanya boleh menyimpan untuk athlete miliknya (athlete.user_id === auth()->id()).
     *   Admin boleh menyimpan untuk athlete mana pun.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'athlete_id'         => 'required|integer|exists:athletes,id',
            'test_type'          => 'required|string|max:100',
            'category'           => 'nullable|string|max:100',
            'result_value'       => 'nullable|numeric',
            'result_display'     => 'nullable|string|max:100',
            'unit'               => 'nullable|string|max:50',
            'duration_sec'       => 'nullable|integer|min:0',
            'is_estimated'       => 'boolean',
            'benchmark_snapshot' => 'nullable|array',
            'achievement'        => 'nullable|numeric|min:0|max:100',
            'performed_at'       => 'required|date',
            'notes'              => 'nullable|string',
            'error_screenshots'  => 'nullable|array',
        ]);

        $user      = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;

        // Authorization: Officer hanya boleh menyimpan untuk athlete miliknya
        $athlete = Athlete::findOrFail($validated['athlete_id']);
        if ($isOfficer && $athlete->user_id !== $user->id) {
            abort(403, 'Unauthorized: you can only record assessments for your own athletes.');
        }

        // user_id selalu dari auth — tidak dari request
        $validated['user_id'] = $user->id;

        $assessment = CameraAssessment::create($validated);

        return response()->json([
            'success'    => true,
            'assessment' => $assessment->load(['athlete:id,name', 'user:id,name']),
        ], 201);
    }

    // ── show ──────────────────────────────────────────────────────────────────

    /**
     * Tampilkan detail satu camera assessment.
     *
     * Officer hanya boleh melihat assessment milik athlete di bawahnya.
     * Admin boleh melihat semua.
     */
    public function show(CameraAssessment $cameraAssessment)
    {
        $user      = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;

        if ($isOfficer) {
            // Load athlete untuk cek ownership — hindari query N+1 saat sudah di-load
            $athlete = $cameraAssessment->athlete ?? $cameraAssessment->load('athlete')->athlete;
            if (!$athlete || $athlete->user_id !== $user->id) {
                abort(403, 'Unauthorized access to this assessment.');
            }
        }

        $cameraAssessment->loadMissing([
            'athlete:id,name,athlete_number,gender,date_of_birth,sport_branch_id',
            'user:id,name',
        ]);

        return Inertia::render('PhysicalAssessment/Show', [
            'assessment' => $cameraAssessment,
        ]);
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    /**
     * Hapus satu camera assessment.
     *
     * Officer hanya boleh menghapus assessment milik athlete-nya.
     * Admin boleh menghapus assessment apa pun.
     */
    public function destroy(CameraAssessment $cameraAssessment)
    {
        $user      = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;

        if ($isOfficer) {
            $athlete = $cameraAssessment->athlete ?? $cameraAssessment->load('athlete')->athlete;
            if (!$athlete || $athlete->user_id !== $user->id) {
                abort(403, 'Unauthorized: you can only delete assessments for your own athletes.');
            }
        }

        $cameraAssessment->delete();

        return redirect()->route('camera-assessments.index')
            ->with('success', 'Assessment record deleted successfully.');
    }
}
