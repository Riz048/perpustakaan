<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
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
    'tahun_terbit',
    'tahun_masuk',
    'sinopsis',
    'keterangan',
    'gambar',
    ];

    public function eksemplar()
    {
        return $this->hasMany(BukuEksemplar::class, 'buku_id', 'id');
    }

    public function getTotalEksemplarAttribute()
    {
        return $this->eksemplar()->count();
    }

    public function getJumlahBaikAttribute()
    {
        return DB::table('riwayat_status_buku as rs')
            ->join('buku_eksemplar as e', 'e.id_eksemplar', '=', 'rs.id_eksemplar')
            ->where('e.buku_id', $this->id)
            ->whereNull('rs.tanggal_selesai')
            ->where('rs.status', 'baik')
            ->count();
    }

    public function getJumlahRusakAttribute()
    {
        return DB::table('riwayat_status_buku as rs')
            ->join('buku_eksemplar as e', 'e.id_eksemplar', '=', 'rs.id_eksemplar')
            ->where('e.buku_id', $this->id)
            ->whereNull('rs.tanggal_selesai')
            ->where('rs.status', 'rusak')
            ->count();
    }

    public function getJumlahHilangAttribute()
    {
        return DB::table('riwayat_status_buku as rs')
            ->join('buku_eksemplar as e', 'e.id_eksemplar', '=', 'rs.id_eksemplar')
            ->where('e.buku_id', $this->id)
            ->whereNull('rs.tanggal_selesai')
            ->where('rs.status', 'hilang')
            ->count();
    }

    public function scopeNonAkademikDenganStok($query)
    {
        return $query->where('kelas_akademik', 'non-akademik')
            ->withCount([
                'eksemplar as buku_tersedia' => function ($q) {
                    $q->whereIn('id_eksemplar', function ($sub) {
                        $sub->select('id_eksemplar')
                            ->from('riwayat_status_buku')
                            ->whereNull('tanggal_selesai')
                            ->where('status', 'baik');
                    })
                    ->whereDoesntHave('peminjamanDetail', fn ($p) =>
                        $p->where('status_transaksi', 'dipinjam')
                    );
                }
            ]);
    }
}
