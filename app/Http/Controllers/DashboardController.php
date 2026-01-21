<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Buku;
use App\Models\BukuEksemplar;
use App\Models\User;
use App\Models\Peminjaman;
use App\Models\KunjunganPerpustakaan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    public function index()
    {
        // Ringkasan umum
        $totalAkademik    = Buku::where('kelas_akademik','!=','non-akademik')->count();
        $totalNonAkademik = Buku::where('kelas_akademik','non-akademik')->count();
        $totalSiswa       = User::where('role','siswa')->count();
        $totalPegawai     = User::whereIn('role',['guru','petugas','kep_perpus','kepsek'])->count();
        $totalGuru        = User::where('role','guru')->count();
        $totalPetugas     = User::whereIn('role', ['petugas','kep_perpus'])->count();
        $kepalaPerpus     = User::where('role','kep_perpus')->first();

        $kunjunganBulanIni = KunjunganPerpustakaan::whereMonth('tanggal_kunjungan', now()->month)
            ->whereYear('tanggal_kunjungan', now()->year)
            ->count();

        $peminjamanBulanIni = Peminjaman::whereMonth('tanggal_pinjam', now()->month)
            ->whereYear('tanggal_pinjam', now()->year)
            ->count();

        // Grafik peminjaman bulanan
        $year = now()->year;
        $peminjamanPerBulan = [];

        for ($i = 1; $i <= 12; $i++) {
            $peminjamanPerBulan[] = Peminjaman::whereYear('tanggal_pinjam', $year)
                ->whereMonth('tanggal_pinjam', $i)
                ->count();
        }

        // Grafik pengunjung bulanan
        $kunjunganBulanan = [];

        for ($i = 1; $i <= 12; $i++) {
            $kunjunganBulanan[] = KunjunganPerpustakaan::whereYear('tanggal_kunjungan', $year)
                ->whereMonth('tanggal_kunjungan', $i)
                ->count();
        }

        // Grafik pengunjung mingguan (5 minggu terakhir)
        $kunjunganMingguan = [];
        $labelMingguan = [];
        $rangeMingguan = [];

        for ($i = 4; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek(Carbon::MONDAY);
            $weekEnd   = now()->subWeeks($i)->endOfWeek(Carbon::SUNDAY);

            $labelMingguan[] = 'Minggu ' . (5 - $i);
            $rangeMingguan[] =
                $weekStart->translatedFormat('d M') .
                ' – ' .
                $weekEnd->translatedFormat('d M');

            $kunjunganMingguan[] = KunjunganPerpustakaan::whereBetween(
                'tanggal_kunjungan',
                [$weekStart, $weekEnd]
            )->count();
        }

        // Grafik pengunjung harian (7 hari terakhir)
        $kunjunganHarian = [];
        $labelHarian = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $labelHarian[] = $date->format('d M');
            $kunjunganHarian[] = KunjunganPerpustakaan::whereDate(
                'tanggal_kunjungan',
                $date
            )->count();
        }

        // Grafik stok buku
        $stokKelas10  = BukuEksemplar::whereHas('buku', fn ($q) => $q->where('kelas_akademik', '10'))->count();
        $stokKelas11  = BukuEksemplar::whereHas('buku', fn ($q) => $q->where('kelas_akademik', '11'))->count();
        $stokKelas12  = BukuEksemplar::whereHas('buku', fn ($q) => $q->where('kelas_akademik', '12'))->count();
        $stokFiksi    = BukuEksemplar::whereHas('buku', fn ($q) => $q->where('kelas_akademik', 'non-akademik')->where('tipe_bacaan', 'fiksi'))->count();
        $stokNonFiksi = BukuEksemplar::whereHas('buku', fn ($q) => $q->where('kelas_akademik', 'non-akademik')->where('tipe_bacaan', 'non-fiksi'))->count();

        $labelStok = ['Kelas 10', 'Kelas 11', 'Kelas 12', 'Fiksi', 'Non-Fiksi'];
        $dataStok  = [$stokKelas10, $stokKelas11, $stokKelas12, $stokFiksi, $stokNonFiksi];

        // Distribusi user
        $userDist = User::select('role', DB::raw('count(*) as total'))
            ->groupBy('role')->get();

        $labelUser = $userDist->pluck('role')->map(fn ($r) => ucfirst($r))->toArray();
        $dataUser  = $userDist->pluck('total')->toArray();

        return view('admin.dashboard', compact(
            'totalAkademik', 'totalNonAkademik',
            'totalSiswa', 'totalPegawai', 'totalGuru', 'totalPetugas', 'kepalaPerpus',
            'peminjamanBulanIni', 'peminjamanPerBulan',
            'kunjunganBulanIni',
            'kunjunganBulanan', 'kunjunganMingguan', 'kunjunganHarian',
            'labelMingguan', 'labelHarian',
            'rangeMingguan',
            'labelStok', 'dataStok', 'labelUser', 'dataUser'
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
            $year  = $request->year;
            $start = Carbon::create($year, 1, 1)->toDateString();
            $end   = Carbon::create($year, 12, 31)->endOfDay();
            $currentYear = $year;
            $queryPeminjaman = Peminjaman::whereYear('tanggal_pinjam', $year);
        }

        $refDate = $end;

        // RINGKASAN
        $totalAkademik = DB::table('buku')
            ->where('kelas_akademik','!=','non-akademik')
            ->count();
        $totalNonAkademik = DB::table('buku')
            ->where('kelas_akademik','non-akademik')
            ->count();
        
        $roleAktif = snapshot(
                DB::table('riwayat_role_user as r'),
                $refDate,
                'r.tanggal_mulai',
                'r.tanggal_selesai'
            );
        $totalPegawai = snapshot(
                DB::table('riwayat_role_user'),
                $refDate,
                'tanggal_mulai',
                'tanggal_selesai'
            )
            ->whereIn('role',['guru','petugas','kep_perpus','kepsek'])
            ->distinct('user_id')
            ->count('user_id');
        $totalSiswa = snapshot(
                DB::table('riwayat_role_user'),
                $refDate,
                'tanggal_mulai',
                'tanggal_selesai'
            )
            ->where('role','siswa')
            ->distinct('user_id')
            ->count('user_id');
        $totalGuru = snapshot(
                DB::table('riwayat_role_user'),
                $refDate,
                'tanggal_mulai',
                'tanggal_selesai'
            )
            ->where('role','guru')
            ->distinct('user_id')
            ->count('user_id');
        $totalPetugas = snapshot(
                DB::table('riwayat_role_user'),
                $refDate,
                'tanggal_mulai',
                'tanggal_selesai'
            )
            ->whereIn('role',['petugas','kep_perpus'])
            ->distinct('user_id')
            ->count('user_id');
        $kepalaPerpus = snapshot(
                DB::table('riwayat_role_user as r'),
                $refDate,
                'r.tanggal_mulai',
                'r.tanggal_selesai'
            )
            ->where('r.role','kep_perpus')
            ->join('user as u','u.id_user','=','r.user_id')
            ->orderBy('r.tanggal_mulai','desc')
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

        // PENGUNJUNG
        $kunjunganBulanan = [];

        $cursor = Carbon::parse($start)->startOfMonth();
        $endCursor = Carbon::parse($end)->endOfMonth();

        while ($cursor <= $endCursor) {
            $kunjunganBulanan[] = [
                'label' => $cursor->translatedFormat('F Y'),
                'total' => DB::table('kunjungan_perpustakaan')
                    ->whereBetween('tanggal_kunjungan', [
                        $cursor->copy()->startOfMonth(),
                        $cursor->copy()->endOfMonth()
                    ])->count()
            ];
            $cursor->addMonth();
        }

        $kunjunganMingguan = [];
        $labelMingguan = [];
        $rangeMingguan = [];

        for ($i = 4; $i >= 0; $i--) {
            $weekStart = $refDate->copy()->subWeeks($i)->startOfWeek(Carbon::MONDAY);
            $weekEnd   = $refDate->copy()->subWeeks($i)->endOfWeek(Carbon::SUNDAY);

            $labelMingguan[] = 'Minggu ' . (5 - $i);
            $rangeMingguan[] =
                $weekStart->translatedFormat('d M') .
                ' – ' .
                $weekEnd->translatedFormat('d M');

            $kunjunganMingguan[] = KunjunganPerpustakaan::whereBetween(
                'tanggal_kunjungan',
                [$weekStart, $weekEnd]
            )->count();
        }

        $kunjunganHarian = [];
        $labelHarian = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = $refDate->copy()->subDays($i);
            $labelHarian[] = $date->format('d M');
            $kunjunganHarian[] = DB::table('kunjungan_perpustakaan')
                ->whereDate('tanggal_kunjungan', $date)
                ->count();
        }

        // BUKU
        $statusBuku = snapshot(
            DB::table('riwayat_status_buku'),
            $refDate
        );

        $totalStok  = DB::table('buku_eksemplar')->count();
        $stokBaik = snapshot(
                DB::table('riwayat_status_buku'),
                $refDate
            )
            ->where(function ($q) {
                $q->whereNull('status')
                ->orWhere('status','baik');
            })
            ->count();
        $stokRusak = snapshot(
                DB::table('riwayat_status_buku'),
                $refDate
            )
            ->where('status','rusak')
            ->count();
        $stokHilang = snapshot(
                DB::table('riwayat_status_buku'),
                $refDate
            )
            ->where('status','hilang')
            ->count();


        // LIST BUKU
        $listBaik = DB::table('buku as b')
            ->leftJoin('buku_eksemplar as e','e.buku_id','=','b.id')
            ->leftJoin('riwayat_status_buku as rs', function ($join) use ($refDate) {
                $join->on('rs.id_eksemplar','=','e.id_eksemplar')
                    ->where('rs.tanggal_mulai','<=',$refDate)
                    ->where(function ($q) use ($refDate) {
                        $q->whereNull('rs.tanggal_selesai')
                        ->orWhere('rs.tanggal_selesai','>=',$refDate);
                    });
            })
            ->select(
                DB::raw("
                    CASE
                        WHEN b.kelas_akademik = '10' THEN 'Buku Kelas 10'
                        WHEN b.kelas_akademik = '11' THEN 'Buku Kelas 11'
                        WHEN b.kelas_akademik = '12' THEN 'Buku Kelas 12'
                        WHEN b.tipe_bacaan = 'fiksi' THEN 'Buku Fiksi'
                        ELSE 'Buku Non-Fiksi'
                    END AS kategori
                "),
                'b.judul',
                'b.pengarang',
                DB::raw("
                    SUM(
                        CASE 
                            WHEN rs.status IS NULL THEN 1
                            WHEN rs.status = 'baik' THEN 1
                            ELSE 0
                        END
                    ) as jumlah
                ")
            )
            ->groupBy('b.id','b.judul','b.pengarang','b.kelas_akademik','b.tipe_bacaan')
            ->havingRaw("
                SUM(
                    CASE 
                        WHEN rs.status IS NULL THEN 1
                        WHEN rs.status = 'baik' THEN 1
                        ELSE 0
                    END
                ) > 0
            ")
            ->get();

        $listRusak = DB::table('buku as b')
            ->leftJoin('buku_eksemplar as e','e.buku_id','=','b.id')
            ->leftJoin('riwayat_status_buku as rs', function ($join) use ($refDate) {
                $join->on('rs.id_eksemplar','=','e.id_eksemplar')
                    ->where('rs.tanggal_mulai','<=',$refDate)
                    ->where(function ($q) use ($refDate) {
                        $q->whereNull('rs.tanggal_selesai')
                        ->orWhere('rs.tanggal_selesai','>=',$refDate);
                    });
            })
            ->select(
                DB::raw("
                    CASE
                        WHEN b.kelas_akademik = '10' THEN 'Buku Kelas 10'
                        WHEN b.kelas_akademik = '11' THEN 'Buku Kelas 11'
                        WHEN b.kelas_akademik = '12' THEN 'Buku Kelas 12'
                        WHEN b.tipe_bacaan = 'fiksi' THEN 'Buku Fiksi'
                        ELSE 'Buku Non-Fiksi'
                    END AS kategori
                "),
                'b.judul',
                'b.pengarang',
                DB::raw("
                    SUM(
                        CASE WHEN rs.status = 'rusak' THEN 1 ELSE 0 END
                    ) as jumlah
                ")
            )
            ->groupBy('b.id','b.judul','b.pengarang','b.kelas_akademik','b.tipe_bacaan')
            ->havingRaw("SUM(CASE WHEN rs.status = 'rusak' THEN 1 ELSE 0 END) > 0")
            ->get();

        $listHilang = DB::table('buku as b')
            ->leftJoin('buku_eksemplar as e','e.buku_id','=','b.id')
            ->leftJoin('riwayat_status_buku as rs', function ($join) use ($refDate) {
                $join->on('rs.id_eksemplar','=','e.id_eksemplar')
                    ->where('rs.tanggal_mulai','<=',$refDate)
                    ->where(function ($q) use ($refDate) {
                        $q->whereNull('rs.tanggal_selesai')
                        ->orWhere('rs.tanggal_selesai','>=',$refDate);
                    });
            })
            ->select(
                DB::raw("
                    CASE
                        WHEN b.kelas_akademik = '10' THEN 'Buku Kelas 10'
                        WHEN b.kelas_akademik = '11' THEN 'Buku Kelas 11'
                        WHEN b.kelas_akademik = '12' THEN 'Buku Kelas 12'
                        WHEN b.tipe_bacaan = 'fiksi' THEN 'Buku Fiksi'
                        ELSE 'Buku Non-Fiksi'
                    END AS kategori
                "),
                'b.judul',
                'b.pengarang',
                DB::raw("
                    SUM(
                        CASE WHEN rs.status = 'hilang' THEN 1 ELSE 0 END
                    ) as jumlah
                ")
            )
            ->groupBy('b.id','b.judul','b.pengarang','b.kelas_akademik','b.tipe_bacaan')
            ->havingRaw("SUM(CASE WHEN rs.status = 'hilang' THEN 1 ELSE 0 END) > 0")
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
                        ELSE 'Buku Non-Fiksi'
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
        $stokKelas10 = (clone $statusBuku)
                        ->join('buku_eksemplar as e','e.id_eksemplar','=','riwayat_status_buku.id_eksemplar')
                        ->join('buku as b','b.id','=','e.buku_id')
                        ->where('b.kelas_akademik','10')
                        ->count();
        $stokKelas11 = (clone $statusBuku)
                        ->join('buku_eksemplar as e','e.id_eksemplar','=','riwayat_status_buku.id_eksemplar')
                        ->join('buku as b','b.id','=','e.buku_id')
                        ->where('b.kelas_akademik','11')
                        ->count();
        $stokKelas12 = (clone $statusBuku)
                        ->join('buku_eksemplar as e','e.id_eksemplar','=','riwayat_status_buku.id_eksemplar')
                        ->join('buku as b','b.id','=','e.buku_id')
                        ->where('b.kelas_akademik','12')
                        ->count();
        $stokFiksi = (clone $statusBuku)
                        ->join('buku_eksemplar as e','e.id_eksemplar','=','riwayat_status_buku.id_eksemplar')
                        ->join('buku as b','b.id','=','e.buku_id')
                        ->where('b.kelas_akademik','fiksi')
                        ->count();
        $stokNonFiksi = (clone $statusBuku)
                        ->join('buku_eksemplar as e','e.id_eksemplar','=','riwayat_status_buku.id_eksemplar')
                        ->join('buku as b','b.id','=','e.buku_id')
                        ->where('b.kelas_akademik','non-fiksi')
                        ->count();

        // jumlah JUDUL
        $judulKelas10 = DB::table('riwayat_status_buku as rs')
                        ->join('buku_eksemplar as e','e.id_eksemplar','=','rs.id_eksemplar')
                        ->join('buku as b','b.id','=','e.buku_id')
                        ->where('rs.tanggal_mulai','<=',$refDate)
                        ->whereIn('rs.id', function ($q) use ($refDate) {
                            $q->select(DB::raw('MAX(id)'))
                            ->from('riwayat_status_buku')
                            ->where('tanggal_mulai','<=',$refDate)
                            ->groupBy('id_eksemplar');
                        })
                        ->where('b.kelas_akademik','10')
                        ->distinct('b.id')
                        ->count('b.id');
        $judulKelas11 = DB::table('riwayat_status_buku as rs')
                        ->join('buku_eksemplar as e','e.id_eksemplar','=','rs.id_eksemplar')
                        ->join('buku as b','b.id','=','e.buku_id')
                        ->where('rs.tanggal_mulai','<=',$refDate)
                        ->whereIn('rs.id', function ($q) use ($refDate) {
                            $q->select(DB::raw('MAX(id)'))
                            ->from('riwayat_status_buku')
                            ->where('tanggal_mulai','<=',$refDate)
                            ->groupBy('id_eksemplar');
                        })
                        ->where('b.kelas_akademik','11')
                        ->distinct('b.id')
                        ->count('b.id');
        $judulKelas12 = DB::table('riwayat_status_buku as rs')
                        ->join('buku_eksemplar as e','e.id_eksemplar','=','rs.id_eksemplar')
                        ->join('buku as b','b.id','=','e.buku_id')
                        ->where('rs.tanggal_mulai','<=',$refDate)
                        ->whereIn('rs.id', function ($q) use ($refDate) {
                            $q->select(DB::raw('MAX(id)'))
                            ->from('riwayat_status_buku')
                            ->where('tanggal_mulai','<=',$refDate)
                            ->groupBy('id_eksemplar');
                        })
                        ->where('b.kelas_akademik','12')
                        ->distinct('b.id')
                        ->count('b.id');
        $judulFiksi = DB::table('riwayat_status_buku as rs')
                        ->join('buku_eksemplar as e','e.id_eksemplar','=','rs.id_eksemplar')
                        ->join('buku as b','b.id','=','e.buku_id')
                        ->where('rs.tanggal_mulai','<=',$refDate)
                        ->whereIn('rs.id', function ($q) use ($refDate) {
                            $q->select(DB::raw('MAX(id)'))
                            ->from('riwayat_status_buku')
                            ->where('tanggal_mulai','<=',$refDate)
                            ->groupBy('id_eksemplar');
                        })
                        ->where('b.kelas_akademik','non-akademik')
                        ->where('tipe_bacaan','fiksi')
                        ->distinct('b.id')
                        ->count('b.id');
        $judulNonFiksi = DB::table('riwayat_status_buku as rs')
                        ->join('buku_eksemplar as e','e.id_eksemplar','=','rs.id_eksemplar')
                        ->join('buku as b','b.id','=','e.buku_id')
                        ->where('rs.tanggal_mulai','<=',$refDate)
                        ->whereIn('rs.id', function ($q) use ($refDate) {
                            $q->select(DB::raw('MAX(id)'))
                            ->from('riwayat_status_buku')
                            ->where('tanggal_mulai','<=',$refDate)
                            ->groupBy('id_eksemplar');
                        })
                        ->where('b.kelas_akademik','non-akademik')
                        ->where('tipe_bacaan','non-fiksi')
                        ->distinct('b.id')
                        ->count('b.id');

        // jumlah EKSEMPLAR
        $eksKelas10 = snapshot(
                DB::table('riwayat_status_buku as rs')
                    ->join('buku_eksemplar as e','e.id_eksemplar','=','rs.id_eksemplar')
                    ->join('buku as b','b.id','=','e.buku_id'),
                $refDate
            )
            ->where('b.kelas_akademik','10')
            ->count();
        $eksKelas11 = snapshot(
                DB::table('riwayat_status_buku as rs')
                    ->join('buku_eksemplar as e','e.id_eksemplar','=','rs.id_eksemplar')
                    ->join('buku as b','b.id','=','e.buku_id'),
                $refDate
            )
            ->where('b.kelas_akademik','11')
            ->count();
        $eksKelas12 = snapshot(
                DB::table('riwayat_status_buku as rs')
                    ->join('buku_eksemplar as e','e.id_eksemplar','=','rs.id_eksemplar')
                    ->join('buku as b','b.id','=','e.buku_id'),
                $refDate
            )
            ->where('b.kelas_akademik','12')
            ->count();
        $eksFiksi = snapshot(
                DB::table('riwayat_status_buku as rs')
                    ->join('buku_eksemplar as e','e.id_eksemplar','=','rs.id_eksemplar')
                    ->join('buku as b','b.id','=','e.buku_id'),
                $refDate
            )
            ->where('b.kelas_akademik','non-akademik')
            ->where('tipe_bacaan','fiksi')
            ->count();
        $eksNonFiksi = snapshot(
                DB::table('riwayat_status_buku as rs')
                    ->join('buku_eksemplar as e','e.id_eksemplar','=','rs.id_eksemplar')
                    ->join('buku as b','b.id','=','e.buku_id'),
                $refDate
            )
            ->where('b.kelas_akademik','non-akademik')
            ->where('tipe_bacaan','non-fiksi')
            ->count();

        $kategori = [
            'Kelas 10' => $stokKelas10,
            'Kelas 11' => $stokKelas11,
            'Kelas 12' => $stokKelas12,
            'Buku Fiksi' => $stokFiksi,
            'Buku Non-Fiksi' => $stokNonFiksi,
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
        $chart1 = $request->chart1; // peminjaman
        $chart2 = $request->chart2; // buku
        $chart3 = $request->chart3; // user
        $chart4 = $request->chart4; // pengunjung

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
            'totalSiswa', 'totalPegawai', 'totalGuru', 'totalPetugas', 'kepalaPerpus',
            'stokBaik','stokRusak','stokHilang',
            'stokKelas10','stokKelas11','stokKelas12','stokFiksi','stokNonFiksi',
            'judulKelas10','judulKelas11','judulKelas12','judulFiksi','judulNonFiksi',
            'eksKelas10','eksKelas11','eksKelas12','eksFiksi','eksNonFiksi',
            'kategori',
            'peminjaman',
            'kunjunganBulanan','kunjunganMingguan','kunjunganHarian',
            'labelMingguan','labelHarian',
            'chart1','chart2','chart3', 'chart4',
            'listBaik','listRusak','listHilang', 'listDipinjam',
            'userDistribusi',
            'listSiswa', 'listGuru', 'listPetugas'
        ))->download('Laporan-Perpustakaan.pdf');
    }
}
