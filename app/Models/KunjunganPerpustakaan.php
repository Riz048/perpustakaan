<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KunjunganPerpustakaan extends Model
{
    protected $table = 'kunjungan_perpustakaan';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'role',
        'tujuan',
        'tanggal_kunjungan',
        'created_at'
    ];
}
