@extends('layouts.admin')

@section('title','Kunjungan Perpustakaan')

@section('konten')
<div class="card">
    <div class="card-body">
        <h5 class="mb-3">Catat Kunjungan</h5>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('kunjungan.store') }}">
            @csrf

            {{-- ROLE --}}
            <div class="form-group">
                <label>Jenis Pengunjung</label>
                <select name="role" id="role" class="form-control" required>
                    <option value="">-- Pilih --</option>
                    <option value="siswa">Siswa</option>
                    <option value="guru">Guru</option>
                    <option value="petugas">Petugas</option>
                    <option value="admin">Admin</option>
                    <option value="kep_perpus">Kepala Perpustakaan</option>
                    <option value="kepsek">Kepala Sekolah</option>
                    <option value="tamu">Tamu</option>
                </select>
            </div>

            {{-- USER TERDAFTAR --}}
            <div class="form-group" id="user-wrapper" style="display:none">
                <label>Nama Pengunjung</label>
                <select name="id_user" id="user-select" class="form-control select-search">
                    <option value="">Pilih User</option>
                    @foreach($users as $usr)
                        <option value="{{ $usr->id_user }}"
                                data-role="{{ $usr->role }}"
                                data-search="
                                    {{ $usr->nama }}
                                    @if($usr->kelasAktif)
                                        {{ $usr->kelasAktif->tingkat }}
                                        {{ $usr->kelasAktif->rombel }}
                                    @endif
                                ">
                            @if($usr->kelasAktif)
                                {{ $usr->kelasAktif->tingkat }}
                                {{ $usr->kelasAktif->rombel }} -
                            @endif
                            {{ $usr->nama }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- NAMA OTOMATIS --}}
            <div class="form-group" id="nama-otomatis" style="display:none">
                <label>Nama Pengunjung</label>
                <input type="text" class="form-control" id="nama-auto" readonly>
                <input type="hidden" name="id_user" id="id-user-auto">
            </div>

            {{-- NAMA TAMU --}}
            <div class="form-group" id="nama-manual" style="display:none">
                <label>Nama Pengunjung</label>
                <input type="text" name="nama_pengunjung" class="form-control"
                       placeholder="Masukkan nama tamu">
            </div>

            {{-- TUJUAN --}}
            <div class="form-group">
                <label>Tujuan Kunjungan</label>
                <select name="tujuan" id="tujuan" class="form-control" required>
                    <option value="">-- Pilih --</option>
                    <option value="baca">Baca</option>
                    <option value="pinjam">Pinjam</option>
                    <option value="kembali">Pengembalian</option>
                    <option value="administrasi">Administrasi</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </div>

            {{-- KETERANGAN --}}
            <div class="form-group" id="keterangan-wrapper" style="display:none">
                <label>Keterangan</label>
                <textarea name="keterangan" class="form-control" rows="3"></textarea>
            </div>

            <button class="btn btn-primary mt-3">Simpan</button>
        </form>
    </div>
</div>

<script>
const petugas = @json($petugas);

document.addEventListener('DOMContentLoaded', function () {
    const role = document.getElementById('role');
    const userWrapper = document.getElementById('user-wrapper');
    const userSelect = document.getElementById('user-select');

    const namaAuto = document.getElementById('nama-otomatis');
    const namaAutoInput = document.getElementById('nama-auto');

    const namaManual = document.getElementById('nama-manual');
    const tujuan = document.getElementById('tujuan');
    const ketWrap = document.getElementById('keterangan-wrapper');

    role.addEventListener('change', function () {
        const r = this.value;

        // reset
        userWrapper.style.display = 'none';
        namaAuto.style.display = 'none';
        namaManual.style.display = 'none';
        namaAutoInput.value = '';
        userSelect.value = '';

        // filter dropdown user
        [...userSelect.options].forEach(opt => {
            if (!opt.dataset.role) return;
            opt.hidden = opt.dataset.role !== r;
        });

        if (['siswa','guru','petugas'].includes(r)) {
            userWrapper.style.display = 'block';
        } 
        else if (['admin','kep_perpus','kepsek'].includes(r)) {
            if (!petugas[r]) {
                alert('User untuk role ini belum dibuat');
                role.value = '';
                return;
            }

            namaAuto.style.display = 'block';
            namaAutoInput.value = petugas[r].nama;
            document.getElementById('id-user-auto').value = petugas[r].id_user;
        }
        else if (r === 'tamu') {
            namaManual.style.display = 'block';
        }
    });

    tujuan.addEventListener('change', function () {
        ketWrap.style.display = this.value === 'lainnya' ? 'block' : 'none';
    });
});
</script>
@endsection
