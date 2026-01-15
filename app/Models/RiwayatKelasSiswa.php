<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatKelasSiswa extends Model
{
    protected $table = 'riwayat_kelas_siswa';

    protected $fillable = [
        'user_id',
        'tingkat',
        'rombel',
        'tahun_ajaran',
        'semester',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }
}
