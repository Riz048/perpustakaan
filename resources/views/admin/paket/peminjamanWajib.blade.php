@extends('layouts.admin')
@section('title','Peminjaman Buku Wajib')

@section('konten')
<div class="container-fluid">

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

    <h1 class="h3 mb-4">Peminjaman Buku Wajib</h1>

    {{-- FILTER --}}
    <form method="GET" class="row mb-3 align-items-end">

        <div class="col-md-3">
            <select name="target" id="target" class="form-control" required>
                <option value="">Pilih Target</option>
                <option value="siswa" {{ request('target')=='siswa'?'selected':'' }}>Siswa</option>
                <option value="guru" {{ request('target')=='guru'?'selected':'' }}>Guru</option>
            </select>
        </div>

        <div class="col-md-3">
            <select name="kelas" id="kelas" class="form-control" required>
                <option value="">Pilih Kelas</option>
                @foreach(['10','11','12'] as $k)
                    <option value="{{ $k }}" {{ request('kelas')==$k?'selected':'' }}>
                        Kelas {{ $k }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3" id="rombelWrap" style="display:none">
            <select name="rombel" id="rombelSelect" class="form-control">
                <option value="">Pilih Rombel</option>
            </select>
        </div>

        <div class="col-md-3">
            <input type="text" name="tahun" class="form-control"
                placeholder="2025/2026" value="{{ request('tahun') }}" required>
        </div>

        <div class="col-md-12 mt-2 text-right">
            <button class="btn btn-primary mr-2">Tampilkan</button>
            <a href="{{ route('peminjaman.wajib.index') }}" class="btn btn-secondary">
                Reset
            </a>
        </div>

    </form>


    {{-- JIKA FILTER DIISI TAPI PAKET TIDAK ADA --}}
    @if(request()->filled('kelas') && request()->filled('tahun') && !$paket)
        <div class="alert alert-danger">
            Tidak ada paket buku <strong>AKTIF</strong> untuk
            Kelas {{ request('kelas') }} – {{ request('tahun') }}.
            <br>
            Aktifkan paket terlebih dahulu di menu <strong>Paket Buku</strong>.
        </div>
    @endif

    @if(!request()->filled('kelas') && !request()->filled('tahun'))
        <div class="alert alert-secondary mt-4">
            <i class="fas fa-info-circle"></i>
            Silakan pilih <strong>Kelas</strong> dan <strong>Tahun Ajaran</strong> terlebih dahulu
            untuk menampilkan data peminjaman buku wajib.
        </div>
    @endif

    {{-- JIKA PAKET ADA --}}
    @if($paket)
    
    @if($paket->detail->isEmpty())
        <div class="alert alert-danger">
            ⚠️ Paket ini aktif tetapi belum memiliki buku.
            Silakan isi detail paket terlebih dahulu.
        </div>
    @endif

        <div class="alert alert-info">
            Paket aktif: <strong>{{ $paket->nama_paket }}</strong>
        </div>

        @if($siswa->isEmpty())
            <div class="alert alert-warning">
                Tidak ada siswa aktif di kelas {{ request('kelas') }}.
            </div>
        @else
            @if ($errors->has('toggle'))
                <div class="alert alert-danger">
                    {{ $errors->first('toggle') }}
                </div>
            @endif

            <div class="row mb-2">
                <div class="col-md-4">
                    <input type="text" id="searchNama" class="form-control form-control-sm"
                        placeholder="Cari nama...">
                </div>
            </div>

            <table class="table table-bordered" id="tabelPaket">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Aksi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($siswa as $s)
                        @php
                            $sudah = $generated->contains($s->id_user);
                        @endphp
                        <tr>
                            <td>{{ $s->nama }}</td>
                            
                            <td>
                                @if($sudah)
                                    <span class="badge badge-success">
                                        Sudah Digenerate
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('peminjaman.wajib.store') }}">
                                        @csrf
                                        <input type="hidden" name="paket_id" value="{{ $paket->id }}">
                                        <input type="hidden" name="id_user" value="{{ $s->id_user }}">
                                        <input type="hidden" name="tanggal_pinjam" value="{{ now()->toDateString() }}">
                                        <input type="hidden" name="lama_pinjam" value="365">

                                        <button class="btn btn-success btn-sm" onclick="this.disabled=true; this.form.submit();">
                                            Generate
                                        </button>
                                    </form>
                                @endif
                            </td>

                            @php
                                $pinjam = \App\Models\Peminjaman::where('id_user', $s->id_user)
                                    ->where('paket_id', $paket->id)
                                    ->where('keterangan', 'BUKU_WAJIB')
                                    ->latest('tanggal_pinjam')
                                    ->first();
                            @endphp

                            <td>
                                @if($pinjam)
                                    @if($pinjam->status === 'sudah dikembalikan')
                                        <span class="badge badge-success">Sudah Dikembalikan</span>
                                    @else
                                        <span class="badge badge-warning">Sudah Digenerate</span>
                                    @endif
                                @else
                                    <span class="badge badge-secondary">Belum Digenerate</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif
</div>
@endsection

<script>
const rombelMap = {
    10: [1,2,3,4,5,6,7,8,9],
    11: [1,2,3,4,5,6,7,8,9],
    12: [1,2,3,4,5,6,7,8,9],
};

document.addEventListener('DOMContentLoaded', () => {
    const target = document.getElementById('target');
    const kelas  = document.getElementById('kelas');
    const rombel = document.getElementById('rombelSelect');
    const wrap   = document.getElementById('rombelWrap');

    function buildRombel(selected = null) {
        rombel.innerHTML = '<option value="">Pilih Rombel</option>';
        if (!kelas.value) return;

        rombelMap[kelas.value].forEach(r => {
            const opt = document.createElement('option');
            opt.value = r;
            opt.textContent = `${kelas.value}-${r}`;
            if (String(r) === String(selected)) opt.selected = true;
            rombel.appendChild(opt);
        });
    }

    function syncUI() {
        if (target.value === 'siswa') {
            wrap.style.display = '';
            buildRombel("{{ request('rombel') }}");
            rombel.required = true;
        } else {
            wrap.style.display = 'none';
            rombel.required = false;
            rombel.innerHTML = '<option value="">Pilih Rombel</option>';
        }
    }

    target.addEventListener('change', syncUI);
    kelas.addEventListener('change', () => {
        if (target.value === 'siswa') buildRombel();
    });

    syncUI();
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchNama');
    const table = document.getElementById('tabelPaket');

    if (!searchInput || !table) return;

    searchInput.addEventListener('keyup', function () {
        const keyword = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const nama = row.cells[0].innerText.toLowerCase();
            row.style.display = nama.includes(keyword) ? '' : 'none';
        });
    });
});
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
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