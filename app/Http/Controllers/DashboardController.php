<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Buku;
use App\Models\BukuEksemplar;
use App\Models\User;
use App\Models\Peminjaman;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    public function index()
    {
        $totalAkademik    = Buku::where('kelas_akademik','!=','non-akademik')->count();
        $totalNonAkademik = Buku::where('kelas_akademik','non-akademik')->count();
        $totalSiswa       = User::where('role','siswa')->count();
        $totalPegawai     = User::whereIn('role',['guru','petugas','kep_perpus','kepsek'])->count();
        $totalGuru        = User::where('role','guru')->count();
        $totalPetugas     = User::whereIn('role', ['petugas','kep_perpus'])->count();
        $kepalaPerpus     = User::where('role','kep_perpus')->first();

        $year = now()->year;
        $peminjamanPerBulan = [];

        for ($i = 1; $i <= 12; $i++) {
            $peminjamanPerBulan[] = Peminjaman::whereYear('tanggal_pinjam',$year)
                ->whereMonth('tanggal_pinjam',$i)
                ->count();
        }

        $stokKelas10 = BukuEksemplar::whereHas('buku', fn($q)=>$q->where('kelas_akademik','10'))->count();
        $stokKelas11 = BukuEksemplar::whereHas('buku', fn($q)=>$q->where('kelas_akademik','11'))->count();
        $stokKelas12 = BukuEksemplar::whereHas('buku', fn($q)=>$q->where('kelas_akademik','12'))->count();
        $stokFiksi   = BukuEksemplar::whereHas('buku', fn($q)=>$q->where('tipe_bacaan','fiksi'))->count();
        $stokNonFiksi= BukuEksemplar::whereHas('buku', fn($q)=>$q->where('tipe_bacaan','non-fiksi'))->count();

        $labelStok = ['Kelas 10','Kelas 11','Kelas 12','Fiksi','Umum'];
        $dataStok  = [$stokKelas10,$stokKelas11,$stokKelas12,$stokFiksi,$stokNonFiksi];

        $userDist = User::select('role', DB::raw('count(*) as total'))
            ->groupBy('role')->get();

        $labelUser = $userDist->pluck('role')->map(fn($r)=>ucfirst($r))->toArray();
        $dataUser  = $userDist->pluck('total')->toArray();

        return view('admin.dashboard', compact(
            'totalAkademik','totalNonAkademik','totalSiswa', 'totalPegawai', 'totalGuru', 'totalPetugas', 'kepalaPerpus',
            'peminjamanPerBulan',
            'labelStok','dataStok',
            'labelUser','dataUser'
        ));
    }

    public function exportPdf(Request $request)
    {
        $sections = collect(json_decode($request->sections, true));

        // PERIODE
        if ($request->mode === 'range') {
            $start = $request->start_date;
            $end   = $request->end_date;
            $currentYear = "$start s/d $end";
            $queryPeminjaman = Peminjaman::whereBetween('tanggal_pinjam', [$start,$end]);
        } else {
            $year = $request->year;
            $currentYear = $year;
            $queryPeminjaman = Peminjaman::whereYear('tanggal_pinjam', $year);
        }

        // RINGKASAN
        $totalAkademik    = Buku::where('kelas_akademik','!=','non-akademik')->count();
        $totalNonAkademik = Buku::where('kelas_akademik','non-akademik')->count();
        $totalSiswa       = User::where('role','siswa')->count();
        $totalPegawai     = User::whereIn('role',['guru','petugas','kep_perpus','kepsek','admin'])->count();
        $totalGuru        = User::where('role','guru')->count();
        $totalPetugas     = User::whereIn('role',['petugas','kep_perpus'])->count();
        $kepalaPerpus     = User::where('role','kep_perpus')->first();

        // PEMINJAMAN
        $peminjaman = [];

        if ($request->mode === 'range') {

            $startDate = Carbon::parse($start)->startOfMonth();
            $endDate   = Carbon::parse($end)->endOfMonth();

            while ($startDate <= $endDate) {
                $peminjaman[] = [
                    'label' => $startDate->translatedFormat('F Y'),
                    'total' => Peminjaman::whereYear('tanggal_pinjam', $startDate->year)
                                ->whereMonth('tanggal_pinjam', $startDate->month)
                                ->count()
                ];

                $startDate->addMonth();
            }

        } else {
            // MODE TAHUNAN
            for ($i = 1; $i <= 12; $i++) {
                $peminjaman[] = [
                    'label' => Carbon::create()->month($i)->translatedFormat('F'),
                    'total' => (clone $queryPeminjaman)
                                ->whereMonth('tanggal_pinjam', $i)
                                ->count()
                ];
            }
        }

        // BUKU
        $totalStok = BukuEksemplar::count();

        $stokRusak = BukuEksemplar::where('status', 'rusak')->count();
        $stokHilang = BukuEksemplar::where('status', 'hilang')->count();
        $stokBaik = BukuEksemplar::where('status', 'baik')
            ->whereDoesntHave('peminjamanDetail', fn($q) =>$q->where('status_transaksi', 'dipinjam'))
            ->count();

        // LIST BUKU
        $listBaik = Buku::withCount([
            'eksemplar as jumlah' => function ($q) {
                $q->where('status','baik')
                ->whereDoesntHave('peminjamanDetail', fn($p)=>$p->where('status_transaksi','dipinjam'));
            }
        ])->get()
        ->filter(fn($b)=>$b->jumlah > 0)
        ->map(function ($buku) {
            return (object)[
                'kategori' => match (true) {
                    $buku->kelas_akademik === '10' => 'Buku Kelas 10',
                    $buku->kelas_akademik === '11' => 'Buku Kelas 11',
                    $buku->kelas_akademik === '12' => 'Buku Kelas 12',
                    $buku->tipe_bacaan === 'fiksi' => 'Buku Fiksi',
                    default => 'Buku Umum (Non-Fiksi)',
                },
                'order_key' => match (true) {
                    $buku->kelas_akademik === '10' => 1,
                    $buku->kelas_akademik === '11' => 2,
                    $buku->kelas_akademik === '12' => 3,
                    $buku->tipe_bacaan === 'fiksi' => 4,
                    default => 5,
                },
                'judul'     => $buku->judul,
                'pengarang' => $buku->pengarang,
                'jumlah'    => $buku->jumlah,
            ];
        })
        ->sortBy('order_key')
        ->values();

        $listRusak = DB::table('buku_eksemplar as e')
            ->join('buku as b', 'b.id', '=', 'e.buku_id')
            ->where('e.status', 'rusak')
            ->select(
                DB::raw("
                    CASE
                        WHEN b.kelas_akademik = '10' THEN 'Buku Kelas 10'
                        WHEN b.kelas_akademik = '11' THEN 'Buku Kelas 11'
                        WHEN b.kelas_akademik = '12' THEN 'Buku Kelas 12'
                        WHEN b.tipe_bacaan = 'fiksi' THEN 'Buku Fiksi'
                        ELSE 'Buku Umum (Non-Fiksi)'
                    END AS kategori
                "),
                'b.judul',
                'b.pengarang',
                DB::raw('COUNT(*) as jumlah')
            )
            ->groupBy(
                'b.id',
                'b.judul',
                'b.pengarang',
                'b.kelas_akademik',
                'b.tipe_bacaan'
            )
            ->get();

        $listHilang = DB::table('buku_eksemplar as e')
            ->join('buku as b', 'b.id', '=', 'e.buku_id')
            ->where('e.status', 'hilang')
            ->select(
                DB::raw("
                    CASE
                        WHEN b.kelas_akademik = '10' THEN 'Buku Kelas 10'
                        WHEN b.kelas_akademik = '11' THEN 'Buku Kelas 11'
                        WHEN b.kelas_akademik = '12' THEN 'Buku Kelas 12'
                        WHEN b.tipe_bacaan = 'fiksi' THEN 'Buku Fiksi'
                        ELSE 'Buku Umum (Non-Fiksi)'
                    END AS kategori
                "),
                'b.judul',
                'b.pengarang',
                DB::raw('COUNT(*) as jumlah')
            )
            ->groupBy(
                'b.id',
                'b.judul',
                'b.pengarang',
                'b.kelas_akademik',
                'b.tipe_bacaan'
            )
            ->get();

        $listDipinjam = DB::table('peminjaman_detail as d')
            ->join('peminjaman as p', 'p.id', '=', 'd.peminjaman_id')
            ->join('buku_eksemplar as e', 'e.id_eksemplar', '=', 'd.eksemplar_id')
            ->join('buku as b', 'b.id', '=', 'e.buku_id')
            ->where('d.status_transaksi', 'dipinjam')
            ->select(
                DB::raw("
                    CASE
                        WHEN b.kelas_akademik = '10' THEN 'Buku Kelas 10'
                        WHEN b.kelas_akademik = '11' THEN 'Buku Kelas 11'
                        WHEN b.kelas_akademik = '12' THEN 'Buku Kelas 12'
                        WHEN b.tipe_bacaan = 'fiksi' THEN 'Buku Fiksi'
                        ELSE 'Buku Umum (Non-Fiksi)'
                    END AS kategori
                "),
                'b.judul',
                'b.pengarang',
                DB::raw('COUNT(*) as jumlah')
            )
            ->groupBy(
                'b.id',
                'b.judul',
                'b.pengarang',
                'b.kelas_akademik',
                'b.tipe_bacaan'
            )
            ->get();

        // SEBARAN BUKU
        $stokKelas10 = BukuEksemplar::whereHas('buku', fn($q)=>$q->where('kelas_akademik','10'))->count();
        $stokKelas11 = BukuEksemplar::whereHas('buku', fn($q)=>$q->where('kelas_akademik','11'))->count();
        $stokKelas12 = BukuEksemplar::whereHas('buku', fn($q)=>$q->where('kelas_akademik','12'))->count();
        $stokFiksi   = BukuEksemplar::whereHas('buku', fn($q)=>$q->where('tipe_bacaan','fiksi'))->count();
        $stokNonFiksi= BukuEksemplar::whereHas('buku', fn($q)=>$q->where('tipe_bacaan','non-fiksi'))->count();

        // jumlah JUDUL
        $judulKelas10 = Buku::where('kelas_akademik','10')->count();
        $judulKelas11 = Buku::where('kelas_akademik','11')->count();
        $judulKelas12 = Buku::where('kelas_akademik','12')->count();
        $judulFiksi = Buku::where('kelas_akademik','non-akademik')->where('tipe_bacaan','fiksi')->count();
        $judulUmum = Buku::where('kelas_akademik','non-akademik')->where('tipe_bacaan','non-fiksi')->count();

        // jumlah EKSEMPLAR
        $eksKelas10 = BukuEksemplar::whereHas('buku', fn($q)=>$q->where('kelas_akademik','10'))->count();
        $eksKelas11 = BukuEksemplar::whereHas('buku', fn($q)=>$q->where('kelas_akademik','11'))->count();
        $eksKelas12 = BukuEksemplar::whereHas('buku', fn($q)=>$q->where('kelas_akademik','12'))->count();
        $eksFiksi = BukuEksemplar::whereHas('buku', fn($q)=>$q->where('kelas_akademik','non-akademik')->where('tipe_bacaan','fiksi'))->count();
        $eksUmum = BukuEksemplar::whereHas('buku', fn($q)=>$q->where('kelas_akademik','non-akademik')->where('tipe_bacaan','non-fiksi'))->count();

        $kategori = [
            'Kelas 10' => $stokKelas10,
            'Kelas 11' => $stokKelas11,
            'Kelas 12' => $stokKelas12,
            'Buku Fiksi' => $stokFiksi,
            'Buku Umum (Non-Fiksi)' => $stokNonFiksi,
        ];

        // USER
        $userDistribusi = User::select('role', DB::raw('count(*) as total'))
            ->groupBy('role')->get();

        // CHART
        $chart1 = $request->chart1;
        $chart2 = $request->chart2;
        $chart3 = $request->chart3;

        $listSiswa = User::with(['kelasAktif'])->where('role', 'siswa')->get();
        $listGuru    = User::where('role','guru')->get();
        $listPetugas = User::where('role','petugas')->get();

        return Pdf::loadView('admin.report', compact(
            'sections','currentYear',
            'totalAkademik','totalNonAkademik',
            'judulKelas10','judulKelas11','judulKelas12','judulFiksi','judulUmum',
            'eksKelas10','eksKelas11','eksKelas12','eksFiksi','eksUmum',
            'totalSiswa', 'totalPegawai', 'totalGuru', 'totalPetugas', 'kepalaPerpus',
            'stokBaik','stokRusak','stokHilang',
            'stokKelas10','stokKelas11','stokKelas12','stokFiksi','stokNonFiksi',
            'kategori',
            'peminjaman',
            'chart1','chart2','chart3',
            'listBaik','listRusak','listHilang', 'listDipinjam',
            'userDistribusi',
            'listSiswa', 'listGuru', 'listPetugas'
        ))->download('Laporan-Perpustakaan.pdf');
    }
}
