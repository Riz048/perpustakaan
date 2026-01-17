<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaketBuku extends Model
{
    protected $table = 'paket_buku';

    public $timestamps = false;

    protected $fillable = [
        'nama_paket',
        'kelas',
        'rombel',
        'target',
        'tahun_ajaran',
        'status_paket',
    ];

    protected $attributes = [
        'status_paket' => 'aktif',
    ];

    public function detail()
    {
        return $this->hasMany(PaketBukuDetail::class, 'paket_id');
    }
}
