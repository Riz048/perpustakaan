<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\RiwayatKelasSiswa;
use App\Models\Petugas;

class User extends Authenticatable
{
    protected $table = 'user';

    protected $primaryKey = 'id_user';

    public $timestamps = false;

    protected $fillable = [
        'nama',
        'username',
        'password',
        'role',
        'kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'telpon',
        'alamat',
        'foto'
    ];

    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'password' => 'hashed',
        'tanggal_lahir' => 'date'
    ];

    public function petugas()
    {
        return $this->hasOne(Petugas::class, 'id_pegawai', 'id_user');
    }

    // Relasi User -> Peminjaman
    public function peminjaman()
    {
        return $this->hasMany(Peminjaman::class, 'id_user');
    }

    // Relasi User -> RiwayatKelas
    public function riwayatKelas()
    {
        return $this->hasMany(RiwayatKelasSiswa::class, 'user_id', 'id_user');
    }

    public function kelasAktif()
    {
        return $this->hasOne(RiwayatKelasSiswa::class, 'user_id', 'id_user')
            ->where('status', 'aktif')
            ->whereIn('tingkat', [10,11,12])
            ->latest('id');
    }
}
