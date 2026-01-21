<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class StatusBukuService
{
    public function ubahManual(
        int $idEksemplar,
        string $statusBaru,
        int $userId,
        string $alasan
    ) {
        DB::transaction(function () use (
            $idEksemplar,
            $statusBaru,
            $userId,
            $alasan
        ) {

            $statusAktif = DB::table('riwayat_status_buku')
                ->where('id_eksemplar', $idEksemplar)
                ->whereNull('tanggal_selesai')
                ->value('status');

            if ($statusAktif === $statusBaru) {
                throw new \Exception(
                    "Status tidak berubah (masih '{$statusAktif}')."
                );
            }

            DB::table('riwayat_status_buku')
                ->where('id_eksemplar', $idEksemplar)
                ->whereNull('tanggal_selesai')
                ->update([
                    'tanggal_selesai' => now(),
                ]);

            DB::table('riwayat_status_buku')->insert([
                'id_eksemplar'    => $idEksemplar,
                'status'          => $statusBaru,
                'tanggal_mulai'   => now(),
                'tanggal_selesai' => null,
                'keterangan'      => '[MANUAL] ' . $alasan,
            ]);

            DB::table('log_status_buku')->insert([
                'id_eksemplar' => $idEksemplar,
                'user_id'      => $userId,
                'perubahan_ke' => $statusBaru,
                'alasan'       => $alasan,
                'tanggal'      => now(),
            ]);
        });
    }
}
