<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\KunjunganPerpustakaan;
use App\Models\User;
use Carbon\Carbon;

class KunjunganController extends Controller
{
    public function index()
    {
        $users = User::with('kelasAktif')
            ->where('status', 'aktif')
            ->orderBy('nama')
            ->get();
        
        $petugas = $users->whereIn('role', ['admin','kep_perpus','kepsek'])
                     ->keyBy('role');

        $statKunjungan = KunjunganPerpustakaan::select('role', DB::raw('COUNT(*) as total'))
            ->whereIn('role', ['siswa','guru','tamu'])
            ->groupBy('role')
            ->pluck('total','role');

        return view('admin.kunjungan', compact('users', 'petugas', 'statKunjungan'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'role' => 'required',
            'tujuan' => 'required',
            'nama_pengunjung' => 'nullable|string',
            'keterangan' => 'nullable|string',
            'id_user' => 'nullable|integer',
            'id_user_admin' => 'nullable|integer',
        ]);

        $idUser = $request->id_user ?? $request->id_user_admin;

        if ($request->nama_pengunjung) {
            $nama = $request->nama_pengunjung;
        } elseif ($idUser) {
            $user = User::where('id_user', $idUser)->first();
            $nama = $user?->nama;
        }

        if (empty($nama)) {
            return back()
                ->withErrors('Nama pengunjung gagal ditentukan')
                ->withInput();
        }

        KunjunganPerpustakaan::create([
            'user_id' => $idUser,
            'nama_pengunjung' => $nama,
            'role' => $request->role,
            'tujuan' => $request->tujuan,
            'keterangan' => $request->keterangan,
            'tanggal_kunjungan' => now()->toDateString(),
        ]);

        return back()->with('success', 'Kunjungan berhasil dicatat');
    }

    public function grafik()
    {
        $data = DB::table('kunjungan_perpustakaan')
            ->selectRaw("
                DATE_FORMAT(tanggal_kunjungan,'%Y-%m') AS bulan,
                COUNT(*) AS total
            ")
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        return view('dashboard.grafik_kunjungan', compact('data'));
    }

    public function tamu(Request $request)
    {
        $query = KunjunganPerpustakaan::query();

        // filter role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // search
        if ($request->filled('nama')) {
            $query->where('nama_pengunjung', 'like', '%'.$request->nama.'%');
        }

        // filter waktu
        $periode = $request->periode ?? 'tahun';

        if ($periode === 'minggu') {
            $query->whereBetween('tanggal_kunjungan', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ]);
        } elseif ($periode === 'bulan') {
            $query->whereMonth('tanggal_kunjungan', now()->month)
                ->whereYear('tanggal_kunjungan', now()->year);
        } else {
            $query->whereYear('tanggal_kunjungan', now()->year);
        }

        $data = $query
            ->orderBy('tanggal_kunjungan', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('admin.kunjungan_tamu', compact('data'));
    }
}
