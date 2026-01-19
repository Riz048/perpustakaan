@extends('layouts.admin')

@section('title','Kunjungan Perpustakaan')

@section('konten')
<div class="container-fluid mt-4">
    @if (session('success'))
    <div id="pageAlert" class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-1"></i>
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
    @endif

    @if ($errors->any())
    <div id="pageAlert" class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle mr-1"></i>
        {{ $errors->first() }}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
    @endif

    <div class="row mb-4">
        @php
            $cards = [
                'siswa' => ['label' => 'Siswa', 'icon' => 'fa-user-graduate', 'color' => 'primary'],
                'guru'  => ['label' => 'Guru',  'icon' => 'fa-chalkboard-teacher', 'color' => 'success'],
                'tamu'  => ['label' => 'Tamu',  'icon' => 'fa-user', 'color' => 'warning'],
            ];
        @endphp

        @foreach ($cards as $role => $cfg)
        <div class="col-md-4 mb-3">
            <div class="card border-left-{{ $cfg['color'] }} shadow-sm h-100">
                <div class="card-body py-3 px-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-xs font-weight-bold text-{{ $cfg['color'] }} text-uppercase mb-1">
                            {{ $cfg['label'] }}
                        </div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">
                            {{ $statKunjungan[$role] ?? 0 }}
                        </div>
                    </div>
                    <i class="fas {{ $cfg['icon'] }} fa-2x text-gray-300"></i>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if(in_array(auth()->user()->role, ['kep_perpus', 'kepsek', 'admin']))
    <div class="mb-3 text-right">
        <a href="{{ route('kunjungan.tamu') }}" class="btn btn-primary btn-sm text-white shadow-sm">
            <i class="fas fa-users mr-1"></i> Daftar Kunjungan Tamu
        </a>
    </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body px-4 py-4">
            <h5 class="mb-4 font-weight-semibold">Catat Kunjungan</h5>

            <form method="POST" action="{{ route('kunjungan.store') }}">
                @csrf

                {{-- ROLE --}}
                <div class="form-group mb-4">
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
                    <label class="font-weight-semibold mb-1">Nama Pengunjung</label>
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
                    <input type="hidden" name="id_user_admin" id="id-user-auto">
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
</div>
@endsection

@section('script')
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

    const allUserOptions = [...userSelect.options].map(opt => opt.cloneNode(true));

    role.addEventListener('change', function () {
        if ($('#user-select').hasClass('select2-hidden-accessible')) {
            $('#user-select').select2('destroy');
        }

        const r = this.value;

        // reset
        userWrapper.style.display = 'none';
        namaAuto.style.display = 'none';
        namaManual.style.display = 'none';
        namaAutoInput.value = '';
        userSelect.value = '';

        $('#user-select').val(null).trigger('change');

        // filter dropdown user
        userSelect.innerHTML = '';

        allUserOptions.forEach(opt => {
            if (!opt.dataset.role || opt.dataset.role === r) {
                userSelect.appendChild(opt.cloneNode(true));
            }
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

        const placeholderMap = {
            siswa: 'Cari nama siswa...',
            guru: 'Cari nama guru...',
            petugas: 'Cari nama petugas...'
        };

        $('#user-select').select2({
            placeholder: placeholderMap[r] ?? 'Cari nama...',
            allowClear: true,
            width: '100%'
        });
    });

    tujuan.addEventListener('change', function () {
        ketWrap.style.display = this.value === 'lainnya' ? 'block' : 'none';
    });
});
</script>

<script>
$(document).ready(function () {
    $('.select-search').select2({
        placeholder: 'Cari nama / kelas / rombel...',
        allowClear: true,
        width: '100%'
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const alertBox = document.getElementById('pageAlert');
    if (!alertBox) return;

    setTimeout(() => {
        alertBox.style.transition = 'opacity 0.6s ease';
        alertBox.style.opacity = '0';

        setTimeout(() => {
            alertBox.remove();
        }, 600);
    }, 3000);
});
</script>
@endsection
