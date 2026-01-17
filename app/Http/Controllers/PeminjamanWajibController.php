<?php

namespace App\Http\Controllers;

use App\Models\BukuEksemplar;
use App\Models\PaketBuku;
use App\Models\Peminjaman;
use App\Models\PeminjamanDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PeminjamanWajibController extends Controller
{
    public function index(Request $request)
    {
        $paket = null;
        $siswa = collect();
        $generated = collect();

        if (
            $request->filled('kelas') &&
            $request->filled('tahun') &&
            $request->filled('target') &&
            ($request->target === 'guru' || $request->filled('rombel'))
        ) {
            if ($request->filled('kelas') && $request->filled('tahun')) {

                $paketQuery = PaketBuku::where('kelas',$request->kelas)
                    ->where('tahun_ajaran',$request->tahun)
                    ->where('target',$request->target)
                    ->where('status_paket','aktif');

                if ($request->target === 'siswa') {
                    $paketQuery->where('rombel',$request->rombel);
                }

                $paket = $paketQuery->first();

                if ($request->target === 'guru') {
                    $siswa = User::where('role','guru')
                        ->orderBy('nama')
                        ->get();
                } else {
                    $siswa = User::with('kelasAktif')
                        ->where('role','siswa')
                        ->whereHas('kelasAktif', function ($q) use ($request) {
                            $q->where('status','aktif')
                            ->where('tingkat', $request->kelas)
                            ->where('rombel', $request->rombel);
                        })
                        ->orderBy('nama')
                        ->get();

                    if ($paket) {
                        $generated = Peminjaman::where('keterangan','BUKU_WAJIB')
                            ->where('paket_id',$paket->id)
                            ->where('status','dipinjam')
                            ->pluck('id_user');
                    }
                }
            }
        }

        return view('admin.paket.peminjamanWajib', compact(
            'paket','siswa','generated'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'paket_id'       => 'required|exists:paket_buku,id',
            'id_user'        => 'required|exists:user,id_user',
            'tanggal_pinjam' => 'required|date',
            'lama_pinjam'    => 'required|numeric|min:1',
        ]);

        $paket = PaketBuku::findOrFail($request->paket_id);

        $exists = Peminjaman::where('id_user', $request->id_user)
            ->where('keterangan', 'BUKU_WAJIB')
            ->where('paket_id', $paket->id)
            ->where('status', 'dipinjam')
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'generate' => 'Peminjaman buku wajib untuk paket ini sudah dibuat.'
            ]);
        }

        try {
            DB::transaction(function () use ($request, $paket) {

                $peminjaman = Peminjaman::create([
                    'paket_id'       => $paket->id,
                    'tanggal_pinjam' => $request->tanggal_pinjam,
                    'lama_pinjam'    => $request->lama_pinjam,
                    'id_user'        => $request->id_user,
                    'id_pegawai'     => Auth::id(),
                    'keterangan'     => 'BUKU_WAJIB',
                    'status'         => 'dipinjam',
                ]);

                foreach ($paket->detail as $detail) {
                    $eksemplars = BukuEksemplar::where('buku_id', $detail->buku_id)
                        ->where('status', 'baik')
                        ->whereDoesntHave('peminjamanDetail', fn($q) =>
                            $q->where('status_transaksi','dipinjam')
                        )
                        ->limit($detail->jumlah)
                        ->get();

                    if ($eksemplars->count() < $detail->jumlah) {
                        throw new \Exception('Stok eksemplar tidak cukup');
                    }

                    foreach ($eksemplars as $eks) {
                        PeminjamanDetail::create([
                            'peminjaman_id'   => $peminjaman->id,
                            'eksemplar_id'    => $eks->id_eksemplar,
                            'kondisi_buku'    => 'baik',
                            'status_transaksi'=> 'dipinjam',
                        ]);
                    }
                }
            });

        } catch (\Throwable $e) {
            return back()->withErrors([
                'generate' => $e->getMessage()
            ]);
        }

        // sukses
        return redirect()->route('peminjaman.wajib.index', [
            'kelas'  => $paket->kelas,
            'rombel' => $paket->rombel,
            'tahun'  => $paket->tahun_ajaran,
            'target' => $paket->target,
        ])->with('success', 'Peminjaman buku wajib berhasil diproses.');
    }
}
