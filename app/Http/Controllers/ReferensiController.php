<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Buku;

class ReferensiController extends Controller
{
    public function home(Request $request)
    {
        $keyword = $request->input('q');

        $kelasSiswa = null;

        // Kalau user login & role siswa
        if (Auth::check() && Auth::user()->role === 'siswa') {
            // Ambil kelas aktif siswa (10/11/12)
            $kelasAktif = Auth::user()->kelasAktif()->first();
            $kelasSiswa = $kelasAktif?->tingkat;
        }

        // Search
        if ($keyword) {

            $query = Buku::where(function ($q) use ($keyword) {
                $q->where('judul', 'like', "%{$keyword}%")
                ->orWhere('pengarang', 'like', "%{$keyword}%")
                ->orWhere('kode_buku', 'like', "%{$keyword}%");
            });

            // Filter kelas
            if ($kelasSiswa) {
                $query->where(function ($q) use ($kelasSiswa) {
                    $q->where('kelas_akademik', $kelasSiswa)
                    ->orWhere('kelas_akademik', 'non-akademik');
                });
            }

            $bukuHasil = $query->orderBy('judul')->get();

            return view('user.referensi.home', [
                'keyword'     => $keyword,
                'bukuHasil'   => $bukuHasil,
                'bukuTerbaru' => collect(),
            ]);
        }

        // Koleksi terbaru
        $query = Buku::orderBy('id', 'desc');

        // Filter kelas
        if ($kelasSiswa) {
            $query->where(function ($q) use ($kelasSiswa) {
                $q->where('kelas_akademik', $kelasSiswa)
                ->orWhere('kelas_akademik', 'non-akademik');
            });
        }

        $bukuTerbaru = $query->limit(12)->get();

        return view('user.referensi.home', [
            'keyword'     => null,
            'bukuHasil'   => null,
            'bukuTerbaru' => $bukuTerbaru,
        ]);
    }

    // List berdasarkan kategori
    public function kategori($kategori)
    {
        $kelasSiswa = null;

        if (Auth::check() && Auth::user()->role === 'siswa') {
            $kelasAktif = Auth::user()->kelasAktif()->first();
            $kelasSiswa = $kelasAktif?->tingkat;
        }

        switch ($kategori) {
            case 'kelas10':
                $judul = "Buku Akademik Kelas 10";
                $buku  = Buku::where('kelas_akademik', '10');
                break;

            case 'kelas11':
                $judul = "Buku Akademik Kelas 11";
                $buku  = Buku::where('kelas_akademik', '11');
                break;

            case 'kelas12':
                $judul = "Buku Akademik Kelas 12";
                $buku  = Buku::where('kelas_akademik', '12');
                break;

            case 'bacaan':
                $judul = "Buku Bacaan";
                $buku = Buku::nonAkademikDenganStok();
                break;

            default:
                abort(404);
        }

        return view('user.referensi.kategori.list', [
            'judul' => $judul,
            'buku'  => $buku->get()
        ]);
    }
}
