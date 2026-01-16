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
        $totalPegawai     = User::whereIn('role',['guru','petugas','kep_perpus','kepsek'])->count();
        $totalSiswa       = User::where('role','siswa')->count();
        $totalGuru        = User::where('role','guru')->count();
        $totalPetugas     = User::whereIn('role',['petugas','kep_perpus'])->count();
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

            $start = Carbon::parse($request->start_date)->startOfDay();
            $end   = Carbon::parse($request->end_date)->endOfDay();
            $currentYear = $start->translatedFormat('d F Y').' – '.$end->translatedFormat('d F Y');

            $queryPeminjaman = Peminjaman::whereBetween('tanggal_pinjam', [$start, $end]);

        } else {

            $year = $request->year;
            $start = Carbon::create($year, 1, 1)->toDateString();
            $end   = Carbon::create($year, 12, 31)->toDateString();
            $currentYear = $year;

            $queryPeminjaman = Peminjaman::whereYear('tanggal_pinjam', $year);
        }

        $refDate = $end;

        // RINGKASAN
        $totalAkademik = DB::table('riwayat_status_buku as rs')
            ->join('buku_eksemplar as e','e.id_eksemplar','=','rs.id_eksemplar')
            ->join('buku as b','b.id','=','e.buku_id')
            ->where('rs.tanggal_mulai','<=',$refDate)
            ->whereIn('rs.id', function ($q) use ($refDate) {
                $q->select(DB::raw('MAX(id)'))
                ->from('riwayat_status_buku')
                ->where('tanggal_mulai','<=',$refDate)
                ->groupBy('id_eksemplar');
            })
            ->where('b.kelas_akademik','!=','non-akademik')
            ->distinct('b.id')
            ->count('b.id');
        $totalNonAkademik = DB::table('riwayat_status_buku as rs')
            ->join('buku_eksemplar as e','e.id_eksemplar','=','rs.id_eksemplar')
            ->join('buku as b','b.id','=','e.buku_id')
            ->where('rs.tanggal_mulai','<=',$refDate)
            ->whereIn('rs.id', function ($q) use ($refDate) {
                $q->select(DB::raw('MAX(id)'))
                ->from('riwayat_status_buku')
                ->where('tanggal_mulai','<=',$refDate)
                ->groupBy('id_eksemplar');
            })
            ->where('b.kelas_akademik', 'non-akademik')
            ->distinct('b.id')
            ->count('b.id');
        
        $roleAktif = snapshot(
                DB::table('riwayat_role_user as r'),
                $refDate,
                'r.tanggal_mulai',
                'r.tanggal_selesai'
            );
        $totalPegawai = (clone $roleAktif)
            ->whereIn('role',['guru','petugas','kep_perpus','kepsek','admin'])
            ->distinct('user_id')
            ->count('user_id');
        $totalSiswa = (clone $roleAktif)
            ->where('role','siswa')
            ->distinct('user_id')
            ->count('user_id');
        $totalGuru = (clone $roleAktif)
            ->where('role','guru')
            ->distinct('user_id')
            ->count('user_id');
        $totalPetugas = (clone $roleAktif)
            ->whereIn('role',['petugas','kep_perpus'])
            ->distinct('user_id')
            ->count('user_id');
        $kepalaPerpus = snapshot(
                DB::table('riwayat_role_user as r')
                    ->join('user as u','u.id_user','=','r.user_id')
                    ->where('r.role','kep_perpus'),
                $refDate
            )
            ->select('u.*')
            ->first();

        // PEMINJAMAN
        $peminjaman = [];

        if ($request->mode === 'range') {

            $startDate = Carbon::parse($start)->startOfMonth();
            $endDate   = Carbon::parse($end)->endOfMonth();

            while ($startDate <= $endDate) {
                $peminjaman[] = [
                    'label' => $startDate->translatedFormat('F Y'),
                    'total' => Peminjaman::whereBetween('tanggal_pinjam', [
                                    $startDate->copy()->startOfMonth(),
                                    $startDate->copy()->endOfMonth()
                                ])->count()
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
        $statusBuku = snapshot(
            DB::table('riwayat_status_buku'),
            $refDate
        );

        $totalStok  = (clone $statusBuku)->count();
        $stokBaik   = (clone $statusBuku)->where('status','baik')->count();
        $stokRusak  = (clone $statusBuku)->where('status','rusak')->count();
        $stokHilang = (clone $statusBuku)->where('status','hilang')->count();

        // LIST BUKU
        $listBaik = snapshot(
                DB::table('riwayat_status_buku as rs'),
                $refDate
            )
            ->join('buku_eksemplar as e','e.id_eksemplar','=','rs.id_eksemplar')
            ->join('buku as b','b.id','=','e.buku_id')
            ->where('rs.status','baik')
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
                DB::raw('COUNT(DISTINCT rs.id_eksemplar) as jumlah')
            )
            ->groupBy(
                'b.id','b.judul','b.pengarang','b.kelas_akademik','b.tipe_bacaan'
            )
            ->get();

        $listRusak = snapshot(
                DB::table('riwayat_status_buku as rs'),
                $refDate
            )
            ->join('buku_eksemplar as e','e.id_eksemplar','=','rs.id_eksemplar')
            ->join('buku as b','b.id','=','e.buku_id')
            ->where('rs.status','rusak')
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
                DB::raw('COUNT(DISTINCT rs.id_eksemplar) as jumlah')
            )
            ->groupBy(
                'b.id','b.judul','b.pengarang','b.kelas_akademik','b.tipe_bacaan'
            )
            ->get();

        $listHilang = snapshot(
                DB::table('riwayat_status_buku as rs'),
                $refDate
            )
            ->join('buku_eksemplar as e','e.id_eksemplar','=','rs.id_eksemplar')
            ->join('buku as b','b.id','=','e.buku_id')
            ->where('rs.status','hilang')
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
                DB::raw('COUNT(DISTINCT rs.id_eksemplar) as jumlah')
            )
            ->groupBy(
                'b.id','b.judul','b.pengarang','b.kelas_akademik','b.tipe_bacaan'
            )
            ->get();

        $listDipinjamBiasa = DB::table('peminjaman as p')
            ->join('peminjaman_detail as d','d.peminjaman_id','=','p.id')
            ->join('buku_eksemplar as e','e.id_eksemplar','=','d.eksemplar_id')
            ->join('buku as b','b.id','=','e.buku_id')
            ->where('p.keterangan','!=','BUKU_WAJIB')
            ->whereBetween('p.tanggal_pinjam',[$start,$end])
            ->select(
                DB::raw("
                    CASE
                        WHEN b.kelas_akademik='10' THEN 'Buku Kelas 10'
                        WHEN b.kelas_akademik='11' THEN 'Buku Kelas 11'
                        WHEN b.kelas_akademik='12' THEN 'Buku Kelas 12'
                        WHEN b.tipe_bacaan='fiksi' THEN 'Buku Fiksi'
                        ELSE 'Buku Umum (Non-Fiksi)'
                    END AS kategori
                "),
                'b.judul',
                'p.tanggal_pinjam',
                DB::raw("
                    CASE
                        WHEN p.status='dipinjam' THEN 'Belum Dikembalikan'
                        ELSE 'Sudah Dikembalikan'
                    END AS status
                "),
                DB::raw('COUNT(DISTINCT d.eksemplar_id) as jumlah')
            )
            ->groupBy(
                'b.id','b.judul','b.kelas_akademik','b.tipe_bacaan',
                'p.tanggal_pinjam','p.status'
            )
            ->get();

        $listDipinjamPaket = DB::table('peminjaman as p')
            ->join('paket_buku as pk', 'pk.id', '=', 'p.paket_id')
            ->join('peminjaman_detail as d', 'd.peminjaman_id', '=', 'p.id')
            ->where('p.keterangan', 'BUKU_WAJIB')
            ->whereBetween('p.tanggal_pinjam', [$start, $end])
            ->select(
                DB::raw("'Buku Wajib / Paket' as kategori"),
                'pk.nama_paket as judul',
                'p.tanggal_pinjam',
                DB::raw("
                    CASE
                        WHEN p.status = 'dipinjam'
                            THEN 'Belum Dikembalikan'
                        ELSE 'Sudah Dikembalikan'
                    END AS status
                "),
                DB::raw('COUNT(d.id) as jumlah')
            )
            ->groupBy(
                'p.id',
                'pk.nama_paket',
                'p.tanggal_pinjam',
                'p.status'
            )
            ->get();

        $listDipinjam = $listDipinjamBiasa
            ->merge($listDipinjamPaket)
            ->sortBy('tanggal_pinjam')
            ->values();

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
        $userDistribusi = snapshot(
                DB::table('riwayat_role_user as r'),
                $refDate,
                'r.tanggal_mulai',
                'r.tanggal_selesai'
            )
            ->select('role', DB::raw('COUNT(DISTINCT user_id) as total'))
            ->groupBy('role')
            ->get();

        // CHART
        $chart1 = $request->chart1;
        $chart2 = $request->chart2;
        $chart3 = $request->chart3;

        $listSiswa = snapshot(
                DB::table('riwayat_role_user as r'),
                $refDate,
                'r.tanggal_mulai',
                'r.tanggal_selesai'
            )
            ->join('user as u','u.id_user','=','r.user_id')
            ->leftJoin('riwayat_kelas_siswa as k', function ($join) use ($refDate) {
                $join->on('k.user_id','=','u.id_user')
                    ->where('k.tanggal_mulai','<=',$refDate)
                    ->where(function ($q) use ($refDate) {
                        $q->whereNull('k.tanggal_selesai')
                        ->orWhere('k.tanggal_selesai','>=',$refDate);
                    });
            })
            ->where('r.role','siswa')
            ->select('u.nama','k.tingkat','k.rombel')
            ->orderBy('k.tingkat')
            ->orderBy('k.rombel')
            ->orderBy('u.nama')
            ->get();
        $listGuru = snapshot(
                DB::table('riwayat_role_user as r')
                    ->join('user as u','u.id_user','=','r.user_id')
                    ->where('r.role','guru'),
                $refDate
            )
            ->select('u.id_user','u.nama')
            ->groupBy('u.id_user','u.nama')
            ->orderBy('u.nama')
            ->get();
        $listPetugas = snapshot(
                DB::table('riwayat_role_user as r'),
                $refDate,
                'r.tanggal_mulai',
                'r.tanggal_selesai'
            )
            ->join('user as u','u.id_user','=','r.user_id')
            ->whereIn('r.role',['kep_perpus','petugas'])
            ->select(
                'u.id_user',
                'u.nama',
                'r.role'
            )
            ->orderByRaw("
                CASE 
                    WHEN r.role = 'kep_perpus' THEN 1
                    ELSE 2
                END
            ")
            ->orderBy('u.nama')
            ->get();

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
