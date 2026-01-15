@extends('layouts.admin')

@section('title', 'Pengaturan — Perpustakaan')

@section('styles')
<style>
    :root { --glass: rgba(255, 255, 255, 0.55); }
    .card-modern { padding: 28px; border-radius: 18px; margin-bottom: 28px; background: var(--glass); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px); border: 1px solid rgba(255, 255, 255, 0.35); box-shadow: var(--card-shadow); animation: fadeIn .55s ease-out; }
    .card-modern h3 { font-size: 20px; font-weight: 700; margin-bottom: 18px; color: var(--primary); }
    .form-control { background: rgba(255, 255, 255, 0.75); border-radius: 12px; border: 1px solid rgba(0, 0, 0, 0.08); padding: 12px 14px; transition: 0.25s ease; }
    .form-control:focus { background: #fff; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(0, 27, 90, 0.15); }
    .btn-primary { background: var(--primary); border-radius: 12px; padding: 10px 22px; border: none; transition: 0.25s; }
    .btn-primary:hover { background: #001b5acc; transform: translateY(-2px); }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endsection

@section('konten')
<div class="container-fluid fade-in">
    <h1 class="h3 mb-4 font-weight-bold text-gray-800">Pengaturan</h1>

    <div class="card-modern">
        <h3>Profil Sekolah</h3>
        <label>Nama Sekolah</label>
        <input type="text" class="form-control mb-2" value="SMA Negeri 1 Hamparan Perak">
        <label>Alamat</label>
        <input type="text" class="form-control mb-2" value="Hamparan Perak, Deli Serdang">
        <label>Email</label>
        <input type="email" class="form-control mb-2" value="perpustakaan@sman1hp.sch.id">
        <label>No Telepon</label>
        <input type="text" class="form-control mb-2" value="08xxxxxxx">
        <button class="btn btn-primary mt-2">Simpan</button>
    </div>

    <div class="card-modern">
        <h3>Profil Admin</h3>
        <label>Nama Admin</label>
        <input type="text" class="form-control mb-2">
        <label>Email Login</label>
        <input type="email" class="form-control mb-2">
        <label>Password Baru</label>
        <input type="password" class="form-control mb-2" placeholder="••••••••">
        <button class="btn btn-primary mt-2">Update</button>
    </div>

    <div class="card-modern">
        <h3>Pengaturan Website</h3>
        <label>Nama Website</label>
        <input type="text" class="form-control mb-2" value="Perpustakaan SMAN 1 HP">
        <label>Logo Website</label>
        <input type="file" class="form-control mb-3">
        <label>Mode Akses Pengunjung</label>
        <select class="form-control">
            <option>Publik</option>
            <option>Login Wajib</option>
        </select>
    </div>

    <div class="card-modern">
        <h3>Pengaturan Sistem</h3>
        <label>Lama Peminjaman (hari)</label>
        <input type="number" class="form-control mb-2" value="7">
        <label>Denda per Hari (Rp)</label>
        <input type="number" class="form-control mb-2" value="1000">
        <label>Batas Maksimal Peminjaman</label>
        <input type="number" class="form-control mb-2" value="3">
    </div>

    <div class="card-modern">
        <h3>Backup & Restore</h3>
        <button class="btn btn-primary mb-3">Backup Database</button>
        <input type="file" class="form-control">
    </div>
</div>
@endsection