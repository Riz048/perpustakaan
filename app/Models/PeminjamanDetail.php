<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeminjamanDetail extends Model
{
    protected $table = 'peminjaman_detail';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'peminjaman_id',
        'eksemplar_id',
        'status_transaksi',
        'kondisi_buku'
    ];

    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class, 'peminjaman_id');
    }

    public function eksemplar()
    {
        return $this->belongsTo(BukuEksemplar::class, 'eksemplar_id', 'id_eksemplar');
    }
}
