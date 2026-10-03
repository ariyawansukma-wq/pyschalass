<?php

namespace App\Http\Controllers;

use App\Models\KaryaIlmiah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LibraryController extends Controller
{
    // ── index (semua role yang bisa view-library) ──────────────────────────────

    public function index(Request $request)
    {
        $query = KaryaIlmiah::orderBy('created_at', 'desc');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        if ($jenis = $request->input('jenis')) {
            $query->where('jenis', $jenis);
        }

        $perPage = min(max((int) $request->input('per_page', 12), 6), 50);
        $karyas  = $query->paginate($perPage)->withQueryString();

        // AJAX untuk filter live — hanya kalau bukan Inertia page visit
        $isAjaxFilter = $request->ajax()
            && !$request->header('X-Inertia')
            && $request->header('Accept') === 'application/json';

        if ($isAjaxFilter) {
            $data = $karyas->map(fn ($k) => [
                'id'             => $k->id,
                'judul'          => $k->judul,
                'jenis'          => $k->jenis,
                'tahun'          => $k->tahun,
                'deskripsi'      => $k->deskripsi,
                'file_url'       => $k->file_path
                    ? Storage::disk('public')->url($k->file_path)
                    : null,
                'link_eksternal' => $k->link_eksternal,
            ]);

            return response()->json([
                'data'      => $data,
                'has_more'  => $karyas->hasMorePages(),
                'next_page' => $karyas->currentPage() + 1,
                'total'     => $karyas->total(),
            ]);
        }

        return Inertia::render('Library/Index', [
            'karyas'  => $karyas,
            'filters' => $request->only(['search', 'jenis', 'per_page']),
        ]);
    }

    // ── store (admin only) ────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $this->authorize('manage-library');

        $validated = $request->validate([
            'judul'          => 'required|string|max:255',
            'jenis'          => 'required|in:Jurnal,Buku,HAKI,Modul,Lainnya',
            'tahun'          => 'required|integer|min:1900|max:2100',
            'deskripsi'      => 'nullable|string',
            'link_eksternal' => 'nullable|url',
            'file_dokumen'   => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $filePath = null;
        if ($request->hasFile('file_dokumen')) {
            $filePath = $request->file('file_dokumen')->store('library_files', 'public');
        }

        KaryaIlmiah::create([
            'judul'          => $validated['judul'],
            'jenis'          => $validated['jenis'],
            'tahun'          => $validated['tahun'],
            'deskripsi'      => $validated['deskripsi'] ?? null,
            'file_path'      => $filePath,
            'link_eksternal' => $validated['link_eksternal'] ?? null,
        ]);

        return back()->with('success', 'Karya ilmiah berhasil ditambahkan!');
    }

    // ── destroy (admin only) ──────────────────────────────────────────────────

    public function destroy(KaryaIlmiah $karyaIlmiah)
    {
        $this->authorize('manage-library');

        if ($karyaIlmiah->file_path && Storage::disk('public')->exists($karyaIlmiah->file_path)) {
            Storage::disk('public')->delete($karyaIlmiah->file_path);
        }

        $karyaIlmiah->delete();

        return back()->with('success', 'Karya ilmiah berhasil dihapus.');
    }
}
