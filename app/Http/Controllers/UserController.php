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
        $query = User::where('role','siswa')
            ->whereHas('siswa', fn($q) => $q->where('status','aktif'));

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
        $users = User::where('role','guru')
            ->whereHas('guru', fn($q) => $q->where('status','aktif'))
            ->orderByDesc('id_user')
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

            $this->syncRoleTables($user);

            // riwayat_role_user
            DB::table('riwayat_role_user')->insert([
                'user_id'        => $user->id_user,
                'role'           => $user->role,
                'tanggal_mulai'  => now()->toDateString(),
                'tanggal_selesai'=> null
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

        DB::transaction(function () use ($request, $user) {

            $oldRole = $user->role;

            // update user
            $data = $request->except(['password','foto','tingkat','rombel','tahun_ajaran','semester']);

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

            if ($oldRole !== $user->role) {

                DB::table('riwayat_role_user')
                    ->where('user_id', $user->id_user)
                    ->whereNull('tanggal_selesai')
                    ->update(['tanggal_selesai' => now()->toDateString()]);

                DB::table('riwayat_role_user')->insert([
                    'user_id' => $user->id_user,
                    'role' => $user->role,
                    'tanggal_mulai' => now()->toDateString(),
                    'tanggal_selesai' => null
                ]);

                $this->syncRoleTables($user);
            }

            if (
                $user->role === 'siswa' &&
                $request->filled(['tingkat','rombel','tahun_ajaran','semester'])
            ) {
                DB::table('riwayat_kelas_siswa')
                    ->where('user_id', $user->id_user)
                    ->where('status','aktif')
                    ->update([
                        'status' => 'non-aktif',
                        'tanggal_selesai' => now()->toDateString()
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
        });

        return back()->with('success','Data user diperbarui');
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

        DB::transaction(function () use ($user) {

            DB::table('petugas')
                ->where('id_pegawai', $user->id_user)
                ->update(['status' => 'non-aktif']);

            DB::table('guru')
                ->where('id_guru', $user->id_user)
                ->update([
                    'status' => 'non-aktif',
                    'tanggal_selesai' => now(),
                    'keterangan' => 'Dihapus dari sistem'
                ]);

            DB::table('siswa')
                ->where('id_siswa', $user->id_user)
                ->update([
                    'status' => 'non-aktif',
                    'tanggal_keluar' => now(),
                    'keterangan' => 'Dihapus dari sistem'
                ]);

            DB::table('user')
                ->where('id_user', $user->id_user)
                ->update(['status' => 'non-aktif']);
        });

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

    public function nonaktif($id)
    {
        DB::transaction(function () use ($id) {

            // nonaktifkan user
            DB::table('user')
                ->where('id_user', $id)
                ->update(['status' => 'non-aktif']);

            // jika siswa → nonaktifkan data siswa
            DB::table('siswa')
                ->where('id_siswa', $id)
                ->where('status', 'aktif')
                ->update([
                    'status' => 'non-aktif',
                    'tanggal_keluar' => now()->toDateString(),
                    'keterangan' => 'Dinonaktifkan dari sistem'
                ]);

            // tutup kelas aktif siswa
            DB::table('riwayat_kelas_siswa')
                ->where('user_id', $id)
                ->where('status', 'aktif')
                ->update([
                    'status' => 'non-aktif',
                    'tanggal_selesai' => now()->toDateString()
                ]);

            // jika petugas → nonaktifkan
            DB::table('petugas')
                ->where('id_pegawai', $id)
                ->update(['status' => 'non-aktif']);

            // jika guru → nonaktifkan
            DB::table('guru')
                ->where('id_guru', $id)
                ->where('status','aktif')
                ->update([
                    'status' => 'non-aktif',
                    'tanggal_selesai' => now()->toDateString(),
                    'keterangan' => 'Dinonaktifkan dari sistem'
                ]);
        });

        return back()->with('success', 'User berhasil dinonaktifkan');
    }

    public function syncRoleTables(User $user)
    {
        $id = $user->id_user;

        DB::table('petugas')->where('id_pegawai', $id)->update(['status' => 'non-aktif']);
        DB::table('guru')->where('id_guru', $id)->update([
            'status' => 'non-aktif',
            'tanggal_selesai' => now(),
        ]);
        DB::table('siswa')->where('id_siswa', $id)->update([
            'status' => 'non-aktif',
            'tanggal_keluar' => now(),
        ]);

        switch ($user->role) {
            case 'petugas':
            case 'admin':
            case 'kepsek':
            case 'kep_perpus':
                DB::table('petugas')->updateOrInsert(
                    ['id_pegawai' => $id],
                    ['status' => 'aktif']
                );
                break;

            case 'guru':
                DB::table('guru')->updateOrInsert(
                    ['id_guru' => $id],
                    [
                        'status' => 'aktif',
                        'tanggal_mulai' => now(),
                        'tanggal_selesai' => null,
                        'keterangan' => 'Aktif sebagai guru'
                    ]
                );
                break;

            case 'siswa':
                DB::table('siswa')->updateOrInsert(
                    ['id_siswa' => $id],
                    [
                        'status' => 'aktif',
                        'tanggal_keluar' => null,
                        'keterangan' => null
                    ]
                );
                break;
        }
    }
}
