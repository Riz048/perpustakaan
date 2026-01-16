<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\BukuEksemplar;
use App\Models\Peminjaman;
use App\Models\PeminjamanDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PeminjamanController extends Controller
{
    public function index()
    {
        $peminjaman = Peminjaman::with(['user', 'petugas', 'detail.eksemplar.buku'])
                        ->orderBy('tanggal_pinjam', 'desc')
                        ->orderBy('id', 'desc')
                        ->get();

        // list nama peminjam, siswa -> guru
        $users = User::with('kelasAktif')
            ->whereIn('role', ['siswa', 'guru'])
            ->get()
            ->sortBy(function ($u) {
                if ($u->role === 'siswa' && $u->kelasAktif) {
                    return sprintf(
                        '1-%02d-%s-%s',
                        $u->kelasAktif->tingkat,
                        $u->kelasAktif->rombel,
                        $u->nama
                    );
                }
                return '2-' . $u->nama;
            });
        
        $bukus = Buku::whereHas('eksemplar', function ($q) {
            $q->where('status', 'baik')
            ->whereDoesntHave('peminjamanDetail', function ($p) {
                $p->where('status_transaksi', 'dipinjam');
            });
        })->get();

        return view('admin.peminjaman', compact('peminjaman', 'users', 'bukus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_user' => 'required|exists:user,id_user',
            'id_buku' => 'required|exists:buku,id',
            'tanggal_pinjam' => 'required|date',
            'lama_pinjam' => 'required|numeric',
        ]);

        $keterangan = $request->keterangan ?? '-';
        
        $isBukuWajib = ($request->keterangan === 'BUKU_WAJIB');

        // hanya batasi kalau buku biasa
        if (!$isBukuWajib) {

            // masih punya buku biasa yang belum dikembalikan
            $masihPinjam = Peminjaman::where('id_user', $request->id_user)
                ->where('status', 'dipinjam')
                ->where('keterangan', '!=', 'BUKU_WAJIB')
                ->exists();

            if ($masihPinjam) {
                return back()->withErrors(
                    'Siswa masih memiliki buku yang belum dikembalikan.'
                );
            }

            // batas minggu kalender (Senin - Minggu)
            $awalMinggu = Carbon::now()->startOfWeek(Carbon::MONDAY);
            $akhirMinggu = Carbon::now()->endOfWeek(Carbon::SUNDAY);

            $sudahPinjamMingguIni = Peminjaman::where('id_user', $request->id_user)
                ->where('keterangan', '!=', 'BUKU_WAJIB')
                ->whereBetween('tanggal_pinjam', [$awalMinggu, $akhirMinggu])
                ->exists();

            if ($sudahPinjamMingguIni) {
                return back()->withErrors(
                    'Siswa hanya boleh meminjam 1 buku biasa dalam satu minggu.'
                );
            }
        }

        $peminjaman = Peminjaman::create([
            'tanggal_pinjam' => $request->tanggal_pinjam,
            'lama_pinjam'    => $request->lama_pinjam,
            'keterangan'     => $keterangan,
            'status'         => 'dipinjam',
            'id_user'        => $request->id_user,
            'id_pegawai'     => Auth::id() ?? 1
        ]);

        $eksemplar = BukuEksemplar::where('buku_id', $request->id_buku)
            ->where('status', 'baik')
            ->whereDoesntHave('peminjamanDetail', function ($q) {
                $q->where('status_transaksi', 'dipinjam');
            })
            ->first();

        if (!$eksemplar) {
            return back()->withErrors('Buku tidak tersedia untuk dipinjam.');
        }

        PeminjamanDetail::create([
        'peminjaman_id'     => $peminjaman->id,
        'eksemplar_id'      => $eksemplar->id_eksemplar,
        'status_transaksi'  => 'dipinjam'
        ]);

        return back()->with('success', 'Transaksi peminjaman berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $pinjam = Peminjaman::findOrFail($id);

        $request->validate([
            'id_user' => 'required|exists:user,id_user',
            'tanggal_pinjam' => 'required|date',
            'lama_pinjam' => 'required|numeric',
        ]);

        // Update peminjaman utama
        $pinjam->update([
            'tanggal_pinjam' => $request->tanggal_pinjam,
            'lama_pinjam'    => $request->lama_pinjam,
            'id_user'        => $request->id_user,
            'keterangan'     => $request->keterangan,
        ]);

        if ($request->id_buku) {
            $detail = $pinjam->detail()->first();

            if ($detail) {
                $eksemplarBaru = BukuEksemplar::where('buku_id', $request->id_buku)
                    ->where('status', 'baik')
                    ->whereDoesntHave('peminjamanDetail', function ($q) {
                        $q->where('status_transaksi', 'dipinjam');
                    })
                    ->first();

                if (!$eksemplarBaru) {
                    return back()->withErrors(
                        'Tidak ada eksemplar buku tersedia untuk diganti.'
                    );
                }

                $detail->update([
                    'eksemplar_id' => $eksemplarBaru->id_eksemplar
                ]);
            }
        }

        return back()->with('success', 'Data peminjaman berhasil diperbarui');
    }
    
    public function destroy($id)
    {
        abort(403, 'Peminjaman tidak boleh dihapus. Hubungi admin.');
    }
}