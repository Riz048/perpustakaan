<?php

namespace App\Http\Controllers;

use App\Models\PaketBuku;
use App\Models\PaketBukuDetail;
use App\Models\Buku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class PaketBukuController extends Controller
{
    public function index(Request $request)
    {
        $tahunTerbaru = PaketBuku::max('tahun_ajaran');

        $pakets = PaketBuku::withSum('detail as total_buku', 'jumlah')

            ->when($request->filled('target'),
                fn ($q) => $q->where('target', $request->target)
            )

            ->when($request->filled('kelas'),
                fn ($q) => $q->where('kelas', $request->kelas)
            )

            ->when(
                $request->filled('rombel') && $request->target === 'siswa',
                fn ($q) => $q->where('rombel', $request->rombel)
            )

            ->when($request->filled('tahun'),
                fn ($q) => $q->where('tahun_ajaran', $request->tahun)
            )

            ->orderBy('kelas')
            ->orderByRaw('CAST(rombel AS UNSIGNED)')
            ->get();

        return view('admin.paket.index', compact('pakets', 'tahunTerbaru'));
    }

    public function create()
    {
        $bukus = Buku::orderBy('judul')->get();

        return view('admin.paket.form', compact('bukus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_paket'   => 'required|string|max:100',
            'kelas'        => 'required|in:10,11,12',
            'rombel'       => $request->target === 'siswa'
                              ? 'required|string|max:10'
                              : 'nullable|string|max:10',
            'target'       => 'required|in:siswa,guru',
            'tahun_ajaran' => 'required|string|max:9',
            'buku_id'      => 'required|array|min:1',
        ]);

        DB::beginTransaction();
        try {

            // Nonaktif paket lama
            $oldPaket = PaketBuku::where('kelas', $request->kelas)
                ->where('tahun_ajaran', $request->tahun_ajaran)
                ->where('target', $request->target);

            if ($request->target === 'siswa') {
                $oldPaket->where('rombel', $request->rombel);
            }

            $oldPaket->update(['status_paket' => 'nonaktif']);

            // Buat paket baru
            $paket = PaketBuku::create([
                'nama_paket'   => $request->nama_paket,
                'kelas'        => $request->kelas,
                'rombel'       => $request->target === 'siswa'
                                  ? $request->rombel
                                  : null,
                'target'       => $request->target,
                'tahun_ajaran' => $request->tahun_ajaran,
                'status_paket' => 'aktif',
            ]);

            foreach ($request->buku_id as $bukuId) {
                PaketBukuDetail::create([
                    'paket_id' => $paket->id,
                    'buku_id'  => $bukuId,
                    'jumlah'   => $request->jumlah[$bukuId] ?? 1,
                ]);
            }

            DB::commit();
            return redirect()->route('paket.index')
                ->with('success', 'Paket buku berhasil disimpan.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors([
                'paket' => $e->getMessage()
            ])->withInput();
        }
    }

    public function edit($id)
    {
        $paket = PaketBuku::with('detail')->findOrFail($id);
        $bukus = Buku::orderBy('judul')->get();

        return view('admin.paket.form', compact('paket', 'bukus'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_paket'   => 'required|string|max:100',
            'kelas'        => 'required|in:10,11,12',
            'rombel'       => $request->target === 'siswa'
                              ? 'required|string|max:10'
                              : 'nullable|string|max:10',
            'target'       => 'required|in:siswa,guru',
            'tahun_ajaran' => 'required|string|max:9',
            'buku_id'      => 'required|array|min:1',
        ]);

        DB::transaction(function () use ($request, $id) {

            $paket = PaketBuku::findOrFail($id);

            $paket->update([
                'nama_paket'   => $request->nama_paket,
                'kelas'        => $request->kelas,
                'rombel'       => $request->target === 'siswa'
                                    ? $request->rombel
                                    : null,
                'target'       => $request->target,
                'tahun_ajaran' => $request->tahun_ajaran,
            ]);

            PaketBukuDetail::where('paket_id', $paket->id)->delete();

            foreach ($request->buku_id as $bukuId) {
                PaketBukuDetail::create([
                    'paket_id' => $paket->id,
                    'buku_id'  => $bukuId,
                    'jumlah'   => $request->jumlah[$bukuId] ?? 1,
                ]);
            }
        });

        return redirect()
            ->route('paket.index')
            ->with('success', 'Paket buku berhasil diperbarui');
    }

    public function toggleStatus($id)
    {
        DB::beginTransaction();
        try {
            $paket = PaketBuku::findOrFail($id);

            if ($paket->status_paket === 'aktif') {
                $paket->update(['status_paket' => 'nonaktif']);
            } else {

                $oldPaket = PaketBuku::where('kelas', $paket->kelas)
                    ->where('tahun_ajaran', $paket->tahun_ajaran)
                    ->where('target', $paket->target);

                if ($paket->target === 'siswa') {
                    $oldPaket->where('rombel', $paket->rombel);
                }

                $oldPaket->update(['status_paket' => 'nonaktif']);

                $paket->update(['status_paket' => 'aktif']);
            }

            DB::commit();
            return back()->with('success', 'Status paket berhasil diubah.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors([
                'toggle' => $e->getMessage()
            ]);
        }
    }
}
