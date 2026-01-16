<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Peminjaman extends Model
{
    protected $table = 'peminjaman';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'paket_id',
        'tanggal_pinjam',
        'lama_pinjam',
        'keterangan',
        'status',
        'id_user',
        'id_pegawai',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function petugas() 
    {
        return $this->belongsTo(User::class, 'id_pegawai', 'id_user');
    }

    public function detail()
    {
        return $this->hasMany(PeminjamanDetail::class, 'peminjaman_id', 'id');
    }

    public function pengembalian()
    {
        return $this->hasOne(Pengembalian::class, 'peminjaman_id');
    }

    public function paket()
    {
        return $this->belongsTo(PaketBuku::class, 'paket_id');
    }

    public function getStatusLabelAttribute()
    {
        if ($this->status === 'dikembalikan') return 'Dikembalikan';

        $batas = Carbon::parse($this->tanggal_pinjam)
                    ->addDays($this->lama_pinjam);

        if (Carbon::today()->gt($batas)) {
            $hari = $batas->diffInDays(Carbon::today());
            return "Terlambat {$hari} hari";
        }

        return 'Dipinjam';
    }
}
