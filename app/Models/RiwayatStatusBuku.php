<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatStatusBuku extends Model
{
    protected $table = 'riwayat_status_buku';
    public $timestamps = false;

    protected $fillable = [
        'id_eksemplar',
        'status',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
    ];

    public function eksemplar()
    {
        return $this->belongsTo(
            BukuEksemplar::class,
            'id_eksemplar',
            'id_eksemplar'
        );
    }
}
