<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\KunjunganPerpustakaan;
use App\Models\User;

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

        return view('admin.kunjungan', compact('users', 'petugas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'role' => 'required',
            'tujuan' => 'required',
            'nama_pengunjung' => 'required_if:user_id,null'
        ]);

        KunjunganPerpustakaan::create([
            'user_id' => $request->user_id,
            'nama_pengunjung' => $request->nama_pengunjung,
            'role' => $request->role,
            'tujuan' => $request->tujuan,
            'tanggal_kunjungan' => now()->toDateString(),
        ]);

        return back()->with('success','Kunjungan berhasil dicatat');
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
}
