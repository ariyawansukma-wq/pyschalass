<?php

namespace App\Http\Controllers;

use App\Models\Screening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ScreeningController extends Controller
{
    // ── index ─────────────────────────────────────────────────────────────────

    /**
     * Riwayat skrining milik user yang sedang login.
     * Mendukung pencarian nama anak + AJAX infinite scroll.
     */
    public function index(Request $request)
    {
        $query = Screening::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc');

        if ($search = $request->input('search')) {
            $query->where('nama_anak', 'like', "%{$search}%");
        }

        $perPage    = min(max((int) $request->input('per_page', 8), 5), 50);
        $screenings = $query->paginate($perPage)->withQueryString();

        // AJAX: kembalikan JSON untuk infinite scroll / live search
        // Hanya kalau bukan Inertia page visit pertama
        $isAjaxFilter = $request->ajax()
            && !$request->header('X-Inertia')
            && $request->header('Accept') === 'application/json';

        if ($isAjaxFilter) {
            $data = $screenings->map(fn ($s) => [
                'id'         => $s->id,
                'nama_anak'  => $s->nama_anak,
                'umur_bulan' => $s->umur_bulan,
                'tanggal'    => $s->created_at->format('d M Y'),
                'total_skor' => $s->total_skor,
                'kategori'   => $s->kategori,
                'url_detail' => route('screening.show', $s->id),
            ]);

            return response()->json([
                'data'      => $data,
                'has_more'  => $screenings->hasMorePages(),
                'next_page' => $screenings->currentPage() + 1,
            ]);
        }

        return Inertia::render('Screening/Index', [
            'screenings' => $screenings,
            'filters'    => $request->only(['search', 'per_page']),
        ]);
    }

    // ── create ────────────────────────────────────────────────────────────────

    /**
     * Tampilkan halaman kamera ISKAD-V (standalone Blade page).
     * Halaman ini menggunakan CDN MediaPipe + vanilla JS — tetap di Blade
     * agar logic deteksi tidak perlu dikonversi ke Vue.
     */
    public function create()
    {
        return view('screening.create');
    }

    // ── store ─────────────────────────────────────────────────────────────────

    /**
     * Terima hasil skrining dari halaman kamera (AJAX POST dari vanilla JS).
     * user_id selalu dari Auth::id() — tidak dari request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_anak'         => 'required|string|max:255',
            'umur_bulan'        => 'required|integer|min:1|max:360',
            'skor_statis'       => 'required|integer|min:0|max:3',
            'skor_tandem'       => 'required|integer|min:0|max:3',
            'skor_lompat'       => 'required|integer|min:0|max:3',
            'skor_sit_to_stand' => 'required|integer|min:0|max:3',
            'skor_vestibular'   => 'required|integer|min:0|max:3',
            'total_skor'        => 'required|integer|min:0|max:15',
            'kategori'          => 'required|string|max:50',
            'chart_data'        => 'nullable|array',
        ]);

        try {
            $screening = Screening::create([
                ...$validated,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success'    => true,
                'message'    => 'Data skrining berhasil disimpan!',
                'id'         => $screening->id,
                'url_detail' => route('screening.show', $screening->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── show ──────────────────────────────────────────────────────────────────

    /**
     * Detail hasil skrining satu anak.
     * Kader hanya bisa lihat skrining miliknya.
     */
    public function show(Screening $screening)
    {
        // Hanya pemilik atau admin yang boleh melihat
        if (Auth::user()->role !== 'admin' && $screening->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this screening.');
        }

        $screening->loadMissing('user:id,name');

        return Inertia::render('Screening/Show', [
            'screening' => $screening,
        ]);
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    /**
     * Hapus data skrining.
     * Admin bisa hapus semua. Kader hanya miliknya.
     */
    public function destroy(Screening $screening)
    {
        if (Auth::user()->role !== 'admin' && $screening->user_id !== Auth::id()) {
            abort(403, 'Unauthorized: Anda tidak bisa menghapus data skrining ini.');
        }

        $screening->delete();

        return redirect()->route('screening.index')
            ->with('success', 'Data skrining berhasil dihapus.');
    }
}
