<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class PetugasController extends Controller
{
    public function index()
    {
        $petugas = User::where('role', 'petugas')
            ->orWhereIn('role', ['admin','kepsek','kep_perpus'])
            ->orderBy('id_user', 'desc')
            ->get();

        return view('admin.petugas', compact('petugas'));
    }

    public function store(Request $request)
    {

        $loginRole = auth()->user()->role;

        if ($loginRole === 'kep_perpus') {
            $request->merge(['role' => 'petugas']);
        }

        $request->validate([
            'nama' => 'required',
            'username' => 'required|unique:user,username',
            'password' => 'required|min:6',
            'role' => 'required|in:petugas,admin,kep_perpus,kepsek',
        ]);

        $user = User::create([
            'nama' => $request->nama,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'telpon' => $request->telpon,
            'alamat' => $request->alamat,
            'kelamin' => $request->kelamin,
            'tempat_lahir' => '-',
            'tanggal_lahir' => date('Y-m-d'),
            'foto' => '',
        ]);

        if ($user->role === 'petugas') {
            \DB::table('petugas')->updateOrInsert(
                ['id_pegawai' => $user->id_user],
                ['status' => 'aktif']
            );
        }

        return back()->with('success','Data petugas berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $loginRole = auth()->user()->role;

        $user = User::findOrFail($id);

        $request->validate([
            'nama' => 'required',
            'username' => 'required|unique:user,username,'.$id.',id_user',
            'role' => 'required|in:petugas,guru,admin,kep_perpus,kepsek',
        ]);

        $data = [
            'nama' => $request->nama,
            'username' => $request->username,
            'role' => $request->role,
            'telpon' => $request->telpon,
            'alamat' => $request->alamat,
            'kelamin' => $request->kelamin,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->refresh();

        // Perubahan role petugas dan != petugas
        if ($request->role === 'petugas') {
            \DB::table('petugas')->updateOrInsert(
                ['id_pegawai' => $id],
                ['status' => 'aktif']
            );
        } else {
            \DB::table('petugas')
                ->where('id_pegawai', $id)
                ->update(['status' => 'non-aktif']);
        }

        return back()->with('success','Data petugas diperbarui');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $loginRole = auth()->user()->role;

        if ($loginRole === 'kep_perpus' && $user->role !== 'petugas') {
            abort(403);
        }

        \DB::table('petugas')
            ->where('id_pegawai', $id)
            ->update(['status' => 'non-aktif']);

        $user->delete();

        return back()->with('success', 'Petugas berhasil dihapus');
    }
}