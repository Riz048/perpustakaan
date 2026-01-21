<?php

namespace App\Http\Controllers;

use App\Services\EksemplarService;
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
        
        $eksemplars = BukuEksemplar::with('buku')
            ->whereIn('id_eksemplar', function ($q) {
                $q->select('id_eksemplar')
                ->from('riwayat_status_buku')
                ->where('status', 'baik')
                ->whereNull('tanggal_selesai');
            })
            ->whereDoesntHave('peminjamanDetail', fn ($q) =>
                $q->where('status_transaksi', 'dipinjam')
            )
            ->get()
            ->groupBy('buku_id');

        return view('admin.peminjaman', compact('peminjaman', 'users', 'eksemplars'));
    }

    public function store(Request $request, EksemplarService $eksemplarService)
    {
        $request->validate([
            'id_user' => 'required|exists:user,id_user',
            'eksemplar_id' => 'required|exists:buku_eksemplar,id_eksemplar',
            'tanggal_pinjam' => 'required|date',
            'lama_pinjam' => 'required|numeric',
        ]);

        $keterangan = $request->keterangan ?? '-';
        $isBukuWajib = ($keterangan === 'BUKU_WAJIB');

        // validasi buku biasa
        if (!$isBukuWajib) {
            $masihPinjam = Peminjaman::where('id_user', $request->id_user)
                ->where('status','dipinjam')
                ->where('keterangan','!=','BUKU_WAJIB')
                ->exists();

            if ($masihPinjam) {
                return back()->withErrors('Siswa masih memiliki buku yang belum dikembalikan.');
            }

            $awalMinggu = Carbon::now()->startOfWeek();
            $akhirMinggu = Carbon::now()->endOfWeek();

            $sudahPinjam = Peminjaman::where('id_user',$request->id_user)
                ->where('keterangan','!=','BUKU_WAJIB')
                ->whereBetween('tanggal_pinjam',[$awalMinggu,$akhirMinggu])
                ->exists();

            if ($sudahPinjam) {
                return back()->withErrors('Siswa hanya boleh meminjam 1 buku per minggu.');
            }
        }

        DB::transaction(function () use ($request, $keterangan, $eksemplarService) {

            $peminjaman = Peminjaman::create([
                'tanggal_pinjam' => $request->tanggal_pinjam,
                'lama_pinjam'    => $request->lama_pinjam,
                'keterangan'     => $keterangan,
                'status'         => 'dipinjam',
                'id_user'        => $request->id_user,
                'id_pegawai'     => auth()->id()
            ]);

            $eksemplar = BukuEksemplar::where('id_eksemplar',$request->eksemplar_id)
                ->whereIn('id_eksemplar', function ($q) {
                    $q->select('id_eksemplar')
                    ->from('riwayat_status_buku')
                    ->where('status','baik')
                    ->whereNull('tanggal_selesai');
                })
                ->whereDoesntHave('peminjamanDetail', fn($q) =>
                    $q->where('status_transaksi','dipinjam')
                )
                ->lockForUpdate()
                ->firstOrFail();

            PeminjamanDetail::create([
                'peminjaman_id'    => $peminjaman->id,
                'eksemplar_id'     => $eksemplar->id_eksemplar,
                'status_transaksi'=> 'dipinjam'
            ]);

            $eksemplarService->ubahStatus(
                $eksemplar->id_eksemplar,
                'dipinjam',
                'peminjaman',
                auth()->id(),
                'Peminjaman buku'
            );
        });

        return back()->with('success','Transaksi peminjaman berhasil ditambahkan');
    }

    public function update(Request $request, $id, EksemplarService $eksemplarService)
    {
        $pinjam = Peminjaman::with('detail')->findOrFail($id);

        DB::transaction(function () use ($request, $pinjam, $eksemplarService) {
            $pinjam->update([
                'tanggal_pinjam' => $request->tanggal_pinjam,
                'lama_pinjam'    => $request->lama_pinjam,
                'id_user'        => $request->id_user,
                'keterangan'     => $request->keterangan,
            ]);

            if ($request->eksemplar_id && $pinjam->detail->isNotEmpty()) {

                $detail = $pinjam->detail->first();
                $eksemplarLama = $detail->eksemplar_id;

                $eksemplarBaru = BukuEksemplar::where('id_eksemplar',$request->eksemplar_id)
                    ->whereIn('id_eksemplar', function ($q) {
                        $q->select('id_eksemplar')
                        ->from('riwayat_status_buku')
                        ->where('status','baik')
                        ->whereNull('tanggal_selesai');
                    })
                    ->whereDoesntHave('peminjamanDetail', fn($q) =>
                        $q->where('status_transaksi','dipinjam')
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $eksemplarService->ubahStatus(
                    $eksemplarLama,
                    'baik',
                    'ganti eksemplar',
                    auth()->id(),
                    'Eksemplar lama dikembalikan'
                );

                $detail->update(['eksemplar_id'=>$eksemplarBaru->id_eksemplar]);

                $eksemplarService->ubahStatus(
                    $eksemplarBaru->id_eksemplar,
                    'dipinjam',
                    'ganti eksemplar',
                    auth()->id(),
                    'Pengganti eksemplar'
                );
            }
        });

        return back()->with('success','Data peminjaman berhasil diperbarui');
    }
    
    public function destroy($id)
    {
        abort(403, 'Peminjaman tidak boleh dihapus. Hubungi admin.');
    }
}