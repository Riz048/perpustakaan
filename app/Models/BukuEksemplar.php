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
        'status'
    ];

    public function buku()
    {
        return $this->belongsTo(Buku::class, 'buku_id');
    }

    public function peminjamanDetail()
    {
        return $this->hasMany(PeminjamanDetail::class, 'eksemplar_id');
    }
}
