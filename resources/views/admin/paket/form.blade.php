@extends('layouts.admin')

@section('title', isset($paket) ? 'Edit Paket Buku' : 'Tambah Paket Buku')

@section('konten')
<div class="container-fluid">

    {{-- NOTIF ERROR --}}
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

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 text-gray-800">
            {{ isset($paket) ? 'Edit Paket Buku' : 'Tambah Paket Buku' }}
        </h1>

        <div>
            <button form="form-paket" class="btn btn-primary" {{ $bukus->count() === 0 ? 'disabled' : '' }}>
                <i class="fas fa-save"></i>
                {{ isset($paket) ? 'Update Paket' : 'Simpan Paket' }}
            </button>
            <a href="{{ route('paket.index') }}" class="btn btn-secondary">
                Batal
            </a>
        </div>
    </div>

    <form id="form-paket" action="{{ isset($paket) ? route('paket.update', $paket->id) : route('paket.store') }}"
        method="POST">
        @csrf
        @isset($paket)
        @method('PUT')
        @endisset

        {{-- INFORMASI PAKET --}}
        <div class="card mb-4">
            <div class="card-header"><strong>Informasi Paket</strong></div>
            <div class="card-body">

                {{-- ROW 1 --}}
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Nama Paket</label>
                        <input type="text" name="nama_paket" class="form-control"
                            value="{{ old('nama_paket', $paket->nama_paket ?? '') }}" required>
                    </div>

                    <div class="col-md-4">
                        <label>Target Peminjaman</label>
                        <select name="target" class="form-control" required>
                            <option value="siswa" {{ old('target',$paket->target ?? '')=='siswa'?'selected':'' }}>Siswa</option>
                            <option value="guru" {{ old('target',$paket->target ?? '')=='guru'?'selected':'' }}>Guru</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" class="form-control"
                            value="{{ old('tahun_ajaran', $paket->tahun_ajaran ?? '') }}" required>
                    </div>
                </div>

                {{-- ROW 2 --}}
                <div class="row">
                    <div class="col-md-4">
                        <label>Kelas</label>
                        <select name="kelas" class="form-control" required>
                            <option value="">-- pilih --</option>
                            @foreach(['10','11','12'] as $k)
                                <option value="{{ $k }}"
                                    {{ old('kelas', $paket->kelas ?? '') == $k ? 'selected' : '' }}>
                                    Kelas {{ $k }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4" id="formRombel" style="display:none;">
                        <label>Rombel</label>
                        <select name="rombel" id="rombelPaket" class="form-control">
                            <option value="">Pilih Rombel</option>
                        </select>
                    </div>

                    <div class="col-md-4"></div>
                </div>

            </div>
        </div>

        {{-- FILTER --}}
        <div class="card mb-4">
            <div class="card-header"><strong>Filter Buku</strong></div>
            <div class="card-body row">
                <div class="col-md-6">
                    <input type="text" id="searchBuku" class="form-control" placeholder="Cari judul buku...">
                </div>

                <div class="col-md-6">
                    <select id="filterKategori" class="form-control">
                        <option value="">Semua Kategori</option>
                        <option value="10">Kelas 10</option>
                        <option value="11">Kelas 11</option>
                        <option value="12">Kelas 12</option>
                        <option value="non-akademik">Non Akademik</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- DAFTAR BUKU --}}
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Pilih Buku</strong>
                <span class="text-muted small">
                    Total dipilih: <strong id="totalDipilih">0</strong> buku
                </span>
            </div>

            <div class="card-body p-0">
                @if($bukus->count() === 0)
                <div class="alert alert-warning mb-3">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Belum ada data buku.</strong><br>
                    Tambahkan data buku terlebih dahulu sebelum membuat paket.
                </div>
                @endif

                <table class="table table-bordered table-hover mb-0" id="tableBuku">
                    <thead class="thead-light">
                        <tr>
                            <th width="60" class="text-center">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th>Judul Buku</th>
                            <th width="160">Kategori</th>
                            <th width="120">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bukus as $buku)
                        @php
                        $detail = isset($paket)
                        ? $paket->detail->firstWhere('buku_id', $buku->id)
                        : null;
                        @endphp

                        <tr id="emptyFilterRow" style="display:none;">
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="fas fa-search"></i><br>
                                Tidak ada buku yang sesuai dengan filter.
                            </td>
                        </tr>

                        <tr class="buku-row" data-judul="{{ strtolower($buku->judul) }}"
                            data-kategori="{{ $buku->kelas_akademik }}">
                            <td class="text-center">
                                <input type="checkbox" class="check-buku" name="buku_id[]" value="{{ $buku->id }}" {{ old('buku_id')
                                                ? in_array($buku->id, old('buku_id', []))
                                                : ($detail ? 'checked' : '') }}>
                            </td>
                            <td>{{ $buku->judul }}</td>
                            <td>
                                <span class="badge badge-info">
                                    {{ $buku->kelas_akademik === 'non-akademik'
                                            ? 'Non Akademik'
                                            : 'Kelas '.$buku->kelas_akademik }}
                                </span>
                            </td>
                            <td>
                                <input type="number" name="jumlah[{{ $buku->id }}]" class="form-control jumlah-input"
                                    min="1" value="{{ old('jumlah.'.$buku->id, $detail->jumlah ?? 1) }}">
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="fas fa-info-circle"></i>
                                Belum ada data buku.<br>
                                Silakan tambahkan buku terlebih dahulu sebelum membuat paket.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </form>
</div>

{{-- SCRIPT --}}
<script>
    const rombelMap = {
        10: [1,2,3,4,5,6,7,8,9],
        11: [1,2,3,4,5,6,7,8,9],
        12: [1,2,3,4,5,6,7,8,9],
    };

    const kelasSelect  = document.querySelector('select[name="kelas"]');
    const targetSelect = document.querySelector('select[name="target"]');
    const rombelSelect = document.getElementById('rombelPaket');
    const rombelWrap   = document.getElementById('formRombel');

    function renderRombel(selected = null) {
        rombelSelect.innerHTML = '<option value="">Pilih Rombel</option>';
        if (!kelasSelect.value) return;

        rombelMap[kelasSelect.value].forEach(r => {
            const opt = document.createElement('option');
            opt.value = r;
            opt.textContent = `${kelasSelect.value}-${r}`;
            if (String(r) === String(selected)) opt.selected = true;
            rombelSelect.appendChild(opt);
        });
    }

    function syncRombel() {
        const isSiswa = targetSelect.value === 'siswa';

        rombelWrap.style.display = isSiswa ? '' : 'none';
        rombelSelect.disabled   = !isSiswa;
        rombelSelect.required   = isSiswa;

        if (!isSiswa) return;

        const selectedRombel = "{{ old('rombel', $paket->rombel ?? '') }}";
        renderRombel(selectedRombel);
    }

    kelasSelect.addEventListener('change', () => {
        if (targetSelect.value === 'siswa') {
            renderRombel();
        }
    });

    targetSelect.addEventListener('change', syncRombel);

    document.addEventListener('DOMContentLoaded', function () {
        syncRombel();
    });
</script>

<script>
    const searchInput = document.getElementById('searchBuku');
    const filterSelect = document.getElementById('filterKategori');
    const checkAllBox = document.getElementById('checkAll');
    const totalDipilih = document.getElementById('totalDipilih');

    searchInput.addEventListener('keyup', filterBuku);
    filterSelect.addEventListener('change', filterBuku);
    checkAllBox.addEventListener('change', toggleAll);

    document.querySelectorAll('.check-buku').forEach(cb => {
        cb.addEventListener('change', updateTotal);
    });

    function filterBuku() {
        const keyword = searchInput.value.toLowerCase();
        const kategori = filterSelect.value;
        let visibleCount = 0;

        document.querySelectorAll('.buku-row').forEach(row => {
            const judul = row.dataset.judul;
            const kat = row.dataset.kategori;

            const matchJudul = judul.includes(keyword);
            const matchKategori = !kategori || kat === kategori;

            const visible = matchJudul && matchKategori;
            row.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });

        document.getElementById('emptyFilterRow').style.display =
            visibleCount === 0 ? '' : 'none';
    }

    function toggleAll() {
        const visibleCheckboxes = document.querySelectorAll(
            '.buku-row:not([style*="display: none"]) .check-buku'
        );

        visibleCheckboxes.forEach(cb => cb.checked = checkAllBox.checked);
        updateTotal();
    }

    function updateTotal() {
        const checked = document.querySelectorAll('.check-buku:checked').length;
        totalDipilih.innerText = checked;
    }

    function sortCheckedToTop() {
        const tbody = document.querySelector('#tableBuku tbody');
        const rows = Array.from(tbody.querySelectorAll('.buku-row'));

        const checkedRows = rows.filter(row =>
            row.querySelector('.check-buku').checked
        );
        const uncheckedRows = rows.filter(row =>
            !row.querySelector('.check-buku').checked
        );

        // kosongkan tbody
        rows.forEach(row => row.remove());

        // masukkan ulang: checked dulu, baru sisanya
        [...checkedRows, ...uncheckedRows].forEach(row => {
            tbody.appendChild(row);
        });
    }

    updateTotal();
    checkAllBox.addEventListener('change', () => {
        toggleAll();
        sortCheckedToTop();
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