<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function show(Request $request, $tipe)
    {
        $kategori = $request->query('kategori');
        $query = Buku::withCount([
            'eksemplar as buku_tersedia' => function ($q) {
                $q->where('status', 'baik')
                ->whereDoesntHave('peminjamanDetail', function ($p) {
                    $p->where('status_transaksi', 'dipinjam');
                });
            }
        ]);

        // Filter fiksi / non-fiksi
        $query->where('tipe_bacaan', $tipe);

        // FIlter role
        if (Auth::check()) {
            $role = Auth::user()->role;

            //  Siswa - Filter kelas
            if ($role === 'siswa') {
                $kelas = Auth::user()->kelas_akademik;

                $query->where(function ($q) use ($kelas) {
                    $q->where('kelas_akademik', $kelas)
                      ->orWhere('kelas_akademik', 'non-akademik');
                });
            }

            // Guru
            else if ($role === 'guru') {
                // tidak difilter
            }

            // Role lain
            else {
                // tidak difilter
            }
        } 
        
        // Guest
        else {
            $query->where('kelas_akademik', 'non-akademik');
        }

        // Filter sub-kategori
        if ($kategori) {
            $query->where('kategori', $kategori);
        }

        // Ambil semua sub kategori unik untuk sidebar
        $daftarKategori = Buku::where('tipe_bacaan', $tipe)
            ->select('kategori')
            ->distinct()
            ->pluck('kategori');

        $buku = $query->orderBy('judul')->get();

        return view('user.referensi.kategori.index', [
            'buku'            => $buku,
            'tipe'            => $tipe,
            'kategoriDipilih' => $kategori,
            'daftarKategori'  => $daftarKategori,
        ]);
    }
}
