<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BukuEksemplar;

class Buku extends Model
{
    protected $table = 'buku';
    public $timestamps = false;

    protected $fillable = [
    'kelas_akademik',
    'tipe_bacaan',
    'kode_buku',
    'judul',
    'nama_penerbit',
    'isbn',
    'pengarang',
    'jlh_hal',
    'tahun_terbit',
    'sinopsis',
    'keterangan',
    'gambar',
    ];

    public function eksemplar()
    {
        return $this->hasMany(BukuEksemplar::class, 'buku_id');
    }

    public function getTotalEksemplarAttribute()
    {
        return $this->eksemplar()->count();
    }

    public function getJumlahBaikAttribute()
    {
        return $this->eksemplar()->where('status', 'baik')->count();
    }

    public function getJumlahRusakAttribute()
    {
        return $this->eksemplar()->where('status', 'rusak')->count();
    }

    public function getJumlahHilangAttribute()
    {
        return $this->eksemplar()->where('status', 'hilang')->count();
    }

    public function scopeNonAkademikDenganStok($query)
    {
        return $query->where('kelas_akademik', 'non-akademik')
            ->withCount([
                'eksemplar as buku_tersedia' => function ($q) {
                    $q->where('status', 'baik')
                    ->whereDoesntHave('peminjamanDetail', fn ($p) =>
                        $p->where('status_transaksi', 'dipinjam')
                    );
                }
            ]);
    }
}
