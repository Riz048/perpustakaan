<?php

namespace App\Services;

use App\Models\BukuEksemplar;
use App\Models\RiwayatStatusBuku;
use Illuminate\Support\Facades\DB;

class EksemplarService
{
    public function ubahStatus(
        int $idEksemplar,
        string $statusBaru,
        string $keterangan,
        ?int $userId = null,
        ?string $alasan = null
    ): void {
        DB::transaction(function () use (
            $idEksemplar,
            $statusBaru,
            $keterangan,
            $userId,
            $alasan
        ) {

            // tutup status lama
            DB::table('riwayat_status_buku')
                ->where('id_eksemplar', $idEksemplar)
                ->whereNull('tanggal_selesai')
                ->update([
                    'tanggal_selesai' => now()
                ]);

            // buka status baru
            DB::table('riwayat_status_buku')->insert([
                'id_eksemplar'    => $idEksemplar,
                'status'          => $statusBaru,
                'tanggal_mulai'   => now(),
                'tanggal_selesai' => null,
                'keterangan'      => $keterangan,
            ]);

            if ($userId) {
                DB::table('log_status_buku')->insert([
                    'id_eksemplar' => $idEksemplar,
                    'user_id'      => $userId,
                    'perubahan_ke' => $statusBaru,
                    'alasan'       => $alasan ?? $keterangan,
                    'tanggal'      => now(),
                ]);
            }
        });
    }
}

