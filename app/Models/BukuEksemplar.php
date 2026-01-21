<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BukuEksemplar extends Model
{
    protected $table = 'buku_eksemplar';
    protected $primaryKey = 'id_eksemplar';
    public $timestamps = false;

    protected $fillable = [
        'buku_id',
        'kode_eksemplar',
    ];

    public function buku()
    {
        return $this->belongsTo(Buku::class, 'buku_id');
    }

    public function peminjamanDetail()
    {
        return $this->hasMany(PeminjamanDetail::class, 'eksemplar_id');
    }

    public function riwayatStatus()
    {
        return $this->hasMany(
            RiwayatStatusBuku::class,
            'id_eksemplar',
            'id_eksemplar'
        );
    }
    
    public function statusAktif()
    {
        return $this->hasOne(RiwayatStatusBuku::class, 'id_eksemplar')
            ->whereNull('tanggal_selesai');
    }
}
