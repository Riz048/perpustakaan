<?php

namespace App\Imports;

use App\Models\Buku;
use App\Models\BukuEksemplar;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class BukuAkademikImport implements ToCollection, WithHeadingRow
{
    public array $errors = [];
    private array $rowsValid = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $row) {

            $required = [
                'kelas_akademik','kode_buku','judul','nama_penerbit',
                'isbn','pengarang','tahun_terbit','tahun_masuk',
                'stok_baik','stok_rusak','stok_hilang'
            ];

            foreach ($required as $col) {
                if (!$row->has($col) || trim((string)$row->get($col)) === '') {
                    $this->errors[] = "Baris ".($i+2).": Kolom {$col} wajib diisi";
                    continue 2;
                }
            }

            if (!in_array($row->get('kelas_akademik'), ['10','11','12'])) {
                $this->errors[] = "Baris ".($i+2).": kelas_akademik harus 10/11/12";
                continue;
            }

            foreach (['stok_baik','stok_rusak','stok_hilang'] as $stok) {
                if (!is_numeric($row->get($stok)) || $row->get($stok) < 0) {
                    $this->errors[] = "Baris ".($i+2).": {$stok} harus angka ≥ 0";
                    continue 2;
                }
            }

            if (
                !preg_match('/^\d{4}$/', $row->get('tahun_terbit')) ||
                !preg_match('/^\d{4}$/', $row->get('tahun_masuk'))
            ) {
                $this->errors[] = "Baris ".($i+2).": Tahun harus format YYYY";
                continue;
            }

            // simpan data valid sementara
            $this->rowsValid[] = $row;
        }

        if (!empty($this->errors)) {
            return;
        }

        DB::beginTransaction();

        try {
            foreach ($this->rowsValid as $row) {

                $buku = Buku::create([
                    'kelas_akademik' => trim($row->get('kelas_akademik')),
                    'tipe_bacaan'    => 'non-fiksi',
                    'kode_buku'      => trim($row->get('kode_buku')),
                    'judul'          => trim($row->get('judul')),
                    'nama_penerbit'  => trim($row->get('nama_penerbit')),
                    'isbn'           => trim($row->get('isbn')),
                    'pengarang'      => trim($row->get('pengarang')),
                    'tahun_terbit'   => $row->get('tahun_terbit'),
                    'tahun_masuk'    => $row->get('tahun_masuk'),
                    'sinopsis'       => trim((string)$row->get('sinopsis')),
                    'keterangan'     => trim((string)$row->get('keterangan')),
                ]);

                $this->buatEksemplar($buku->id, 'baik',   (int)$row->get('stok_baik'));
                $this->buatEksemplar($buku->id, 'rusak',  (int)$row->get('stok_rusak'));
                $this->buatEksemplar($buku->id, 'hilang', (int)$row->get('stok_hilang'));
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->errors[] = "Gagal menyimpan data buku";
        }
    }

    private function buatEksemplar($bukuId, $status, $jumlah)
    {
        for ($i = 1; $i <= $jumlah; $i++) {

            $eksemplar = BukuEksemplar::create([
                'buku_id'        => $bukuId,
                'kode_eksemplar' => uniqid('EK-'),
                'status'         => $status,
            ]);

            DB::table('riwayat_status_buku')->insert([
                'id_eksemplar'    => $eksemplar->id_eksemplar,
                'status'          => $status,
                'tanggal_mulai'   => now(),
                'tanggal_selesai' => null,
                'keterangan'      => 'Import buku'
            ]);
        }
    }
}
