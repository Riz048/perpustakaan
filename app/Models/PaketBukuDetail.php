<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaketBukuDetail extends Model
{
    protected $table = 'paket_buku_detail';

    public $timestamps = false;

    protected $fillable = [
        'paket_id',
        'buku_id',
        'jumlah',
    ];

    public function buku()
    {
        return $this->belongsTo(Buku::class, 'buku_id');
    }
}
