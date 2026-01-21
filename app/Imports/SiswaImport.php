<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class SiswaImport implements ToCollection, WithHeadingRow
{
    public array $errors = [];
    private array $validatedRows = [];
    private array $usernameDalamFile = [];

    public function collection(Collection $rows)
    {
        // validasi semua baris
        foreach ($rows as $index => $row) {

            if (!$row->has('nama') || !$row->has('username')) {
                $this->errors[] = "Baris ".($index + 2).": Header tidak sesuai template";
                continue;
            }

            if (!$row->get('nama') || !$row->get('username')) {
                $this->errors[] = "Baris ".($index + 2).": Nama / Username kosong";
                continue;
            }

            if (!$row->get('password')) {
                $this->errors[] = "Baris ".($index + 2).": Password wajib diisi";
                continue;
            }

            if (User::where('username', $row->get('username'))->exists()) {
                $this->errors[] = "Baris ".($index + 2).": Username sudah digunakan";
                continue;
            }

            $kelamin = strtolower(trim($row->get('kelamin')));
            if (!in_array($kelamin, ['pria', 'wanita'])) {
                $this->errors[] = "Baris ".($index + 2).": Kelamin harus Pria atau Wanita";
                continue;
            }

            $tingkat = (int) $row->get('tingkat');
            if (!in_array($tingkat, [10, 11, 12])) {
                $this->errors[] = "Baris ".($index + 2).": Tingkat harus 10, 11, atau 12";
                continue;
            }

            if (!preg_match('/^[1-9]$/', $row->get('rombel'))) {
                $this->errors[] = "Baris ".($index + 2).": Rombel harus angka 1–9";
                continue;
            }

            if (!preg_match('/^\d{4}\/\d{4}$/', $row->get('tahun_ajaran'))) {
                $this->errors[] = "Baris ".($index + 2).": Tahun ajaran harus format 2024/2025";
                continue;
            }

            $semester = strtolower(trim($row->get('semester')));
            if (!in_array($semester, ['ganjil', 'genap'])) {
                $this->errors[] = "Baris ".($index + 2).": Semester harus Ganjil atau Genap";
                continue;
            }

            $username = trim($row->get('username'));

            // cek duplikat di file Excel
            if (in_array($username, $this->usernameDalamFile)) {
                $this->errors[] = "Baris ".($index + 2).": Username '{$username}' duplikat di file Excel";
                continue;
            }
            $this->usernameDalamFile[] = $username;

            // cek sudah ada di database
            if (User::where('username', $username)->exists()) {
                $this->errors[] = "Baris ".($index + 2).": Username '{$username}' sudah terdaftar";
                continue;
            }

            // jika data cocok semua -> validasi
            $this->validatedRows[] = [
                'nama'          => $row->get('nama'),
                'username'      => $row->get('username'),
                'password'      => $row->get('password'),
                'kelamin'       => $kelamin,
                'tingkat'       => $tingkat,
                'rombel'        => strtoupper($row->get('rombel')),
                'tahun_ajaran'  => $row->get('tahun_ajaran'),
                'semester'      => $semester,
                'telpon'        => $row->get('telpon') ?? '-',
                'alamat'        => $row->get('alamat') ?? '-',
            ];
        }

        // error -> batal
        if (!empty($this->errors)) {
            return;
        }

        // simpan data
        DB::beginTransaction();

        try {
            foreach ($this->validatedRows as $data) {

                $user = User::create([
                    'nama'     => $data['nama'],
                    'username' => $data['username'],
                    'password' => Hash::make($data['password']),
                    'role'     => 'siswa',
                    'kelamin'  => $data['kelamin'],
                    'telpon'   => $data['telpon'],
                    'alamat'   => $data['alamat'],
                ]);

                // tabel siswa
                DB::table('siswa')->insert([
                    'id_siswa'        => $user->id_user,
                    'status'          => 'aktif',
                    'tanggal_keluar'  => null,
                    'keterangan'      => 'Import siswa'
                ]);

                // riwayat_role_user
                DB::table('riwayat_role_user')->insert([
                    'user_id'        => $user->id_user,
                    'role'           => 'siswa',
                    'tanggal_mulai'  => now()->toDateString(),
                    'tanggal_selesai'=> null,
                ]);

                // riwayat_kelas_siswa
                DB::table('riwayat_kelas_siswa')->insert([
                    'user_id'       => $user->id_user,
                    'tingkat'       => $data['tingkat'],
                    'rombel'        => $data['rombel'],
                    'tahun_ajaran'  => $data['tahun_ajaran'],
                    'semester'      => $data['semester'],
                    'status'        => 'aktif',
                    'tanggal_mulai' => now()->toDateString(),
                ]);

                // log user
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
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->errors[] = "Gagal menyimpan data siswa ke database";
        }
    }
}
