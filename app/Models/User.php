<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\RiwayatKelasSiswa;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\Petugas;

class User extends Authenticatable
{
    protected $table = 'user';
    protected $primaryKey = 'id_user';
    public $timestamps = false;

    protected $fillable = [
        'nama','username','password','role',
        'kelamin','tempat_lahir','tanggal_lahir',
        'telpon','alamat','foto'
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'hashed',
        'tanggal_lahir' => 'date'
    ];

    public function petugas()
    {
        return $this->hasOne(Petugas::class, 'id_pegawai', 'id_user');
    }

    public function guru()
    {
        return $this->hasOne(Guru::class, 'id_guru', 'id_user');
    }

    public function getStatusGuruAttribute()
    {
        if ($this->role !== 'guru') return null;
        return $this->guru->status ?? 'aktif';
    }

    public function siswa()
    {
        return $this->hasOne(Siswa::class, 'id_siswa', 'id_user');
    }

    public function getStatusSiswaAttribute()
    {
        if ($this->role !== 'siswa') return null;
        return $this->siswa->status ?? 'aktif';
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
