<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class GuruImport implements ToCollection, WithHeadingRow
{
    public array $errors = [];

    private array $validatedRows = [];

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

            // jika data cocok semua -> validasi
            $this->validatedRows[] = [
                'nama'     => $row->get('nama'),
                'username' => $row->get('username'),
                'password' => $row->get('password'),
                'kelamin'  => $kelamin,
                'telpon'   => $row->get('telpon') ?? '-',
                'alamat'   => $row->get('alamat') ?? '-',
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
                User::create([
                    'nama'     => $data['nama'],
                    'username' => $data['username'],
                    'password' => Hash::make($data['password']),
                    'role'     => 'guru',
                    'kelamin'  => $data['kelamin'],
                    'telpon'   => $data['telpon'],
                    'alamat'   => $data['alamat'],
                ]);
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->errors[] = "Gagal menyimpan data guru ke database";
        }
    }
}
