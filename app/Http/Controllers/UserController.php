<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\User;
use App\Exports\SiswaTemplateExport;
use App\Imports\SiswaImport;
use App\Exports\GuruTemplateExport;
use App\Imports\GuruImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    // list siswa
    public function siswa(Request $request)
    {
        $query = User::with('kelasAktif')
            ->where('role', 'siswa');

        $tingkat = $request->tingkat;

        if ($tingkat) {
            $query->whereHas('kelasAktif', function ($q) use ($tingkat) {
                $q->where('tingkat', $tingkat);
            });
        }

        $users = $query->orderBy('id_user', 'desc')->get();

        return view('admin.siswa', compact('users', 'tingkat'));
    }

    // list guru
    public function guru()
    {
        $users = User::where('role', 'guru')
            ->orderBy('id_user', 'desc')
            ->get();

        return view('admin.guru', compact('users'));
    }

    // tambah user
    public function store(Request $request)
    {
        // validasi dasar
        $request->validate([
            'nama'     => 'required',
            'username' => 'required|unique:user,username',
            'password' => 'required|min:6',
            'role'     => 'required|in:siswa,guru,petugas,admin,kep_perpus,kepsek',
            'kelamin'  => 'required',
        ]);

        DB::beginTransaction();

        try {
            // simpan user utama
            $user = User::create([
                'nama'          => $request->nama,
                'username'      => $request->username,
                'password'      => Hash::make($request->password),
                'role'          => $request->role,
                'kelamin'       => $request->kelamin,
                'tempat_lahir'  => $request->tempat_lahir ?? '-',
                'tanggal_lahir' => $request->tanggal_lahir ?? null,
                'telpon'        => $request->telpon ?? '-',
                'alamat'        => $request->alamat ?? '-',
                'foto'          => $request->hasFile('foto')
                    ? $request->file('foto')->store('foto_users', 'public')
                    : null,
            ]);

            // khusus siswa → buat kelas aktif pertama
            if ($user->role === 'siswa') {
                $request->validate([
                    'tingkat' => 'required|in:10,11,12',
                    'rombel' => 'required|string|max:1',
                    'tahun_ajaran' => 'required',
                    'semester' => 'required',
                ]);

                DB::table('riwayat_kelas_siswa')->insert([
                    'user_id' => $user->id_user,
                    'tingkat' => $request->tingkat,
                    'rombel' => strtoupper($request->rombel),
                    'tahun_ajaran' => $request->tahun_ajaran,
                    'semester' => $request->semester,
                    'status' => 'aktif',
                    'tanggal_mulai' => now()->toDateString()
                ]);
            }

            // log aktivitas
            DB::table('log_user')->insert([
                'id_user'    => $user->id_user,
                'role'       => $user->role,
                'nama'       => $user->nama,
                'username'   => $user->username,
                'password'   => '-',
                'kelamin'    => $user->kelamin,
                'action'     => 'inserted',
                'changed_on' => now(),
                'changed_by' => auth()->id(),
            ]);

            DB::commit();
            $redirect = match ($user->role) {
                'siswa' => 'users.siswa',
                'guru'  => 'users.guru',
                default => 'petugas.index',
            };

            return redirect()->route($redirect)->with('success','User berhasil ditambahkan');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors($e->getMessage());
        }
    }

    // update user
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'nama'     => 'required',
            'username' => 'required|unique:user,username,' . $id . ',id_user',
            'role'     => 'required|in:siswa,guru,petugas,admin,kep_perpus,kepsek',
        ]);

        DB::beginTransaction();
        try {
            $oldRole = $user->role;

            // update user
            $data = $request->except(['password','foto']);
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }
            if ($request->hasFile('foto')) {
                if ($user->foto && Storage::disk('public')->exists($user->foto)) {
                    Storage::disk('public')->delete($user->foto);
                }
                $data['foto'] = $request->file('foto')->store('foto_users','public');
            }

            $user->update($data);

            // riwayat role
            if ($oldRole !== $request->role) {
                DB::table('riwayat_role_user')
                    ->where('user_id', $user->id_user)
                    ->whereNull('tanggal_selesai')
                    ->update([
                        'tanggal_selesai' => now()->toDateString()
                    ]);

                DB::table('riwayat_role_user')->insert([
                    'user_id' => $user->id_user,
                    'role' => $request->role,
                    'tanggal_mulai' => now()->toDateString(),
                    'tanggal_selesai' => null
                ]);
            }

            // sinkron petugas
            if ($request->role === 'petugas') {
                DB::table('petugas')->updateOrInsert(
                    ['id_pegawai' => $user->id_user],
                    ['status' => 'aktif']
                );
            } else {
                DB::table('petugas')
                    ->where('id_pegawai', $user->id_user)
                    ->update(['status' => 'non-aktif']);
            }

            // khusus siswa → kelas
            if ($request->role === 'siswa') {
                DB::table('riwayat_kelas_siswa')
                    ->where('user_id', $user->id_user)
                    ->where('status','aktif')
                    ->update(['status'=>'lulus']);

                DB::table('riwayat_kelas_siswa')->insert([
                    'user_id' => $user->id_user,
                    'tingkat' => $request->tingkat,
                    'rombel' => strtoupper($request->rombel),
                    'tahun_ajaran' => $request->tahun_ajaran,
                    'semester' => $request->semester,
                    'status' => 'aktif',
                    'tanggal_mulai' => now()->toDateString()
                ]);
            }

            DB::commit();
            return back()->with('success','Data user diperbarui');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors($e->getMessage());
        }
    }

    // hapus user
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // hapus foto jika ada
        if ($user->foto && Storage::disk('public')->exists($user->foto)) {
            Storage::disk('public')->delete($user->foto);
        }

        DB::table('petugas')
            ->where('id_pegawai', $user->id_user)
            ->update(['status' => 'non-aktif']);

        $user->delete();

        $route = match ($user->role) {
            'siswa' => 'users.siswa',
            'guru'  => 'users.guru',
            default => 'petugas.index',
        };

        return redirect()->route($route)->with('success', 'User berhasil dihapus');
    }

    // riwayat peminjaman
    public function riwayat()
    {
        $riwayat = Peminjaman::where('id_user', Auth::id())
            ->with('detail.eksemplar.buku')
            ->orderByDesc('tanggal_pinjam')
            ->get();

        return view('user.transaksi.riwayat', compact('riwayat'));
    }

    public function downloadTemplateSiswa()
    {
        return Excel::download(new SiswaTemplateExport, 'template_import_siswa.xlsx');
    }

    public function importSiswa(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:2048',
        ]);

        $import = new SiswaImport();
        Excel::import($import, $request->file('file'));

        if (!empty($import->errors)) {
            return back()->with('error_import', $import->errors);
        }

        return back()->with('success', 'Data siswa berhasil diimport');
    }

    public function downloadTemplateGuru()
    {
        return Excel::download(new GuruTemplateExport, 'template_import_guru.xlsx');
    }

    public function importGuru(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx|max:2048'
        ]);

        $import = new GuruImport();
        Excel::import($import, $request->file('file'));

        if (!empty($import->errors)) {
            return back()->with('error_import', $import->errors);
        }

        return back()->with('success','Data guru berhasil diimport');
    }
}
