<?php

namespace App\Http\Controllers;

use App\Services\EksemplarService;
use App\Models\Pengembalian;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use DB;

class PengembalianController extends Controller
{
    public function index()
    {
        $pengembalian = Pengembalian::with([
                'peminjaman.detail.eksemplar',
                'peminjaman.detail.eksemplar.buku',
                'user'
            ])
            ->orderBy('tanggal_kembali', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $peminjamanAktif = Peminjaman::with(['user', 'detail'])
            ->where('status', 'dipinjam')
            ->when(auth()->user()->role === 'petugas', function ($q) {
                $q->where('keterangan', '!=', 'BUKU_WAJIB');
            })
            ->orderBy('tanggal_pinjam', 'asc')
            ->get();

        return view('admin.pengembalian', compact('pengembalian', 'peminjamanAktif'));
    }

    public function store(Request $request, EksemplarService $eksemplarService)
    {
        if (Pengembalian::where('peminjaman_id', $request->peminjaman_id)->exists()) {
            return back()->withErrors('Peminjaman ini sudah dikembalikan.');
        }

        $request->validate([
            'peminjaman_id' => 'required|exists:peminjaman,id',
        ]);

        $mode = $request->mode_pengembalian ?? 'biasa';

        if ($mode === 'biasa') {
            $request->validate([
                'status_kondisi' => 'required|in:baik,rusak,hilang',
            ]);
        }

        if (
            $mode === 'biasa' &&
            in_array($request->status_kondisi ?? '', ['rusak','hilang'])
        ) {
            $request->validate([
                'foto_bukti' => 'required|image|max:2048'
            ]);
        }

        $pinjam = Peminjaman::with('detail')->findOrFail($request->peminjaman_id);

        // mode pengembalian
        $mode = $request->mode_pengembalian ?? 'biasa';

        // kunci role
        if (
            $mode === 'paket' &&
            !in_array(auth()->user()->role, ['kep_perpus','admin','kepsek'])
        ) {
            return back()->withErrors('Pengembalian buku wajib hanya bisa dilakukan oleh Kep. Perpus / Admin / Kepsek.');
        }

        if ($pinjam->detail->isEmpty()) {
            return back()->withErrors('Detail peminjaman tidak ditemukan.');
        }

        try {
            DB::transaction(function () use (
                $request,
                $eksemplarService,
                $pinjam,
                $mode,
                &$pengembalian
            ) {

                // foto bukti
                $path = '-';
                if ($request->hasFile('foto_bukti')) {
                    $path = $request->file('foto_bukti')->store('bukti_kembali', 'public');
                }

                // simpan pengembalian
                $pengembalian = Pengembalian::create([
                    'peminjaman_id'   => $pinjam->id,
                    'tanggal_kembali' => now(),
                    'id_user'         => $pinjam->id_user,
                    'foto_bukti'      => $path
                ]);

                $details = $pinjam->detail;

                if ($mode === 'biasa') {
                    $details = $details->take(1);
                }

                foreach ($details as $detail) {

                    $kondisi = $mode === 'paket'
                        ? ($request->kondisi[$detail->id] ?? 'baik')
                        : $request->status_kondisi;

                    if (!$detail->eksemplar) {
                        throw new \Exception(
                            'Eksemplar tidak ditemukan (detail ID: '.$detail->id.')'
                        );
                    }

                    $detail->update([
                        'kondisi_buku'     => $kondisi,
                        'status_transaksi' => 'dikembalikan'
                    ]);

                    // ubah status
                    $eksemplarService->ubahStatus(
                        $detail->eksemplar->id_eksemplar,
                        $kondisi,
                        'pengembalian',
                        auth()->id(),
                        $mode === 'paket'
                            ? 'Pengembalian buku wajib'
                            : 'Pengembalian buku biasa'
                    );

                    if (in_array($kondisi, ['rusak','hilang'])) {
                        DB::table('log_pengembalian_bermasalah')->insert([
                            'pengembalian_id' => $pengembalian->id,
                            'id_eksemplar'    => $detail->eksemplar->id_eksemplar,
                            'user_id'         => auth()->id(),
                            'kondisi'         => $kondisi,
                            'catatan'         => 'Dikembalikan dalam kondisi ' . $kondisi,
                            'tanggal'         => now()
                        ]);
                    }
                }

                $pinjam->update(['status' => 'dikembalikan']);
            });

        } catch (\Throwable $e) {
            return back()->withErrors($e->getMessage());
        }

        return redirect()
            ->route('pengembalian')
            ->with('success', 'Buku berhasil dikembalikan.');
    }

    public function getDetailPaket($id)
    {
        $pinjam = Peminjaman::with('detail.eksemplar.buku')->findOrFail($id);

        if ($pinjam->keterangan !== 'BUKU_WAJIB') {
            return response()->json([]);
        }

        return response()->json(
            $pinjam->detail->map(function ($d) {
                return [
                    'id'      => $d->id,
                    'judul'   => $d->eksemplar->buku->judul ?? '-',
                    'kondisi' => $d->kondisi_buku
                ];
            })
        );
    }

    public function update(Request $request, $id, EksemplarService $eksemplarService)
    {
        $pengembalian = Pengembalian::with('peminjaman.detail')->findOrFail($id);

        $pinjam = $pengembalian->peminjaman;

        // Buku biasa
        if ($pinjam->keterangan !== 'BUKU_WAJIB') {

            $baru = $request->status_kondisi;

            $detail = $pinjam->detail->first();

            if (!$detail) {
                return back()->withErrors('Detail peminjaman tidak ditemukan.');
            }

            $lama = $detail->kondisi_buku;

            $detail->update([
                'kondisi_buku' => $baru
            ]);

            $eksemplarService->ubahStatus(
                $detail->eksemplar->id_eksemplar,
                $baru,
                'edit pengembalian'
            );

            return back()->with('success', 'Status buku berhasil diperbarui.');
        }

        // Buku paket
        foreach ($pinjam->detail as $detail) {
            if (!isset($request->kondisi[$detail->id])) continue;

            $lama = $detail->kondisi_buku;
            $baru = $request->kondisi[$detail->id];

            $detail->update(['kondisi_buku' => $baru]);

            $eksemplarService->ubahStatus(
                $detail->eksemplar->id_eksemplar,
                $baru,
                'edit pengembalian paket'
            );
        }

        return back()->with('success', 'Status buku paket berhasil diperbarui.');
    }

    public function updatePaket(Request $request, $id, EksemplarService $eksemplarService)
    {
        $pinjam = Peminjaman::with('detail')->findOrFail($id);

        $details = $pinjam->detail;

        foreach ($details as $detail) {
            if (!isset($request->kondisi[$detail->id])) continue;

            $baru = $request->kondisi[$detail->id];

            $detail->update(['kondisi_buku' => $baru]);

            $eksemplarService->ubahStatus(
                $detail->eksemplar->id_eksemplar,
                $baru,
                'edit pengembalian paket',
                auth()->id(),
                'Edit kondisi buku paket'
            );
        }

        return back()->with('success', 'Kondisi buku paket diperbarui.');
    }

    public function getEditPaket($id)
    {
        $pinjam = Peminjaman::with('detail.eksemplar.buku')->findOrFail($id);

        abort_if($pinjam->keterangan !== 'BUKU_WAJIB', 403);

        return response()->json(
            $pinjam->detail->map(function ($d) {
                return [
                    'id'      => $d->id,
                    'judul'   => $d->eksemplar->buku->judul ?? '-',
                    'kondisi' => $d->kondisi_buku
                ];
            })
        );
    }

    public function destroy($id)
    {
        abort(403, 'Pengembalian tidak boleh dihapus.');
    }
}
