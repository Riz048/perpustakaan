<?php

namespace App\Imports;

use App\Models\Buku;
use App\Models\BukuEksemplar;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class BukuNonAkademikImport implements ToCollection, WithHeadingRow
{
    public array $errors = [];
    private array $validatedRows = [];
    private array $kodeBukuDalamFile = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $row) {

            $required = [
                'tipe_bacaan','kode_buku','judul','nama_penerbit',
                'isbn','pengarang','tahun_terbit','tahun_masuk',
                'stok_baik','stok_rusak','stok_hilang'
            ];

            foreach ($required as $col) {
                if (!$row->has($col) || trim((string)$row->get($col)) === '') {
                    $this->errors[] = "Baris ".($i+2).": Kolom {$col} wajib diisi";
                    continue 2;
                }
            }

            $tipe = strtolower(trim($row->get('tipe_bacaan')));
            if (!in_array($tipe, ['fiksi','non-fiksi'])) {
                $this->errors[] = "Baris ".($i+2).": Tipe bacaan harus fiksi atau non-fiksi";
                continue;
            }

            foreach (['stok_baik','stok_rusak','stok_hilang'] as $stok) {
                if (!is_numeric($row->get($stok)) || $row->get($stok) < 0) {
                    $this->errors[] = "Baris ".($i+2).": {$stok} harus angka ≥ 0";
                    continue 2;
                }
            }

            if (!preg_match('/^\d{4}$/', $row->get('tahun_terbit')) ||
                !preg_match('/^\d{4}$/', $row->get('tahun_masuk'))) {
                $this->errors[] = "Baris ".($i+2).": Tahun harus format YYYY";
                continue;
            }

            $kodeBuku = trim($row->get('kode_buku'));

            // cek duplikat di file Excel
            if (in_array($kodeBuku, $this->kodeBukuDalamFile)) {
                $this->errors[] = "Baris ".($i+2).": kode_buku '{$kodeBuku}' duplikat di file Excel";
                continue;
            }
            $this->kodeBukuDalamFile[] = $kodeBuku;

            // cek sudah ada di database
            if (Buku::where('kode_buku', $kodeBuku)->exists()) {
                $this->errors[] = "Baris ".($i+2).": kode_buku '{$kodeBuku}' sudah ada di database";
                continue;
            }

            $this->validatedRows[] = [
                'tipe_bacaan'   => $tipe,
                'kode_buku'     => $row->get('kode_buku'),
                'judul'         => $row->get('judul'),
                'nama_penerbit' => $row->get('nama_penerbit'),
                'isbn'          => $row->get('isbn'),
                'pengarang'     => $row->get('pengarang'),
                'tahun_terbit'  => $row->get('tahun_terbit'),
                'tahun_masuk'   => $row->get('tahun_masuk'),
                'stok_baik'     => (int)$row->get('stok_baik'),
                'stok_rusak'    => (int)$row->get('stok_rusak'),
                'stok_hilang'   => (int)$row->get('stok_hilang'),
                'sinopsis'      => $row->get('sinopsis'),
                'keterangan'    => $row->get('keterangan'),
            ];
        }

        if (!empty($this->errors)) {
            return;
        }

        DB::beginTransaction();

        try {
            foreach ($this->validatedRows as $data) {

                $buku = Buku::create([
                    'kelas_akademik' => 'non-akademik',
                    'tipe_bacaan'    => $data['tipe_bacaan'],
                    'kode_buku'      => trim($data['kode_buku']),
                    'judul'          => $data['judul'],
                    'nama_penerbit'  => $data['nama_penerbit'],
                    'isbn'           => $data['isbn'],
                    'pengarang'      => $data['pengarang'],
                    'tahun_terbit'   => $data['tahun_terbit'],
                    'tahun_masuk'    => $data['tahun_masuk'],
                    'sinopsis'       => $data['sinopsis'],
                    'keterangan'     => $data['keterangan'],
                ]);

                $this->buatEksemplar($buku->id, 'baik',   $data['stok_baik']);
                $this->buatEksemplar($buku->id, 'rusak',  $data['stok_rusak']);
                $this->buatEksemplar($buku->id, 'hilang', $data['stok_hilang']);
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->errors[] = "Gagal menyimpan data buku non-akademik";
        }
    }

    private function buatEksemplar($bukuId, $status, $jumlah)
    {
        for ($i = 1; $i <= $jumlah; $i++) {

            $idEksemplar = DB::table('buku_eksemplar')->insertGetId([
                'buku_id' => $bukuId,
                'kode_eksemplar' => uniqid('EK-'),
            ]);

            DB::table('riwayat_status_buku')->insert([
                'id_eksemplar' => $idEksemplar,
                'status' => $status,
                'tanggal_mulai' => now()->toDateString(),
                'tanggal_selesai' => null,
            ]);
        }
    }
}
