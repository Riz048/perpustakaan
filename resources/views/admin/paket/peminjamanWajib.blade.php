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
    <form method="GET" class="row mb-3">

        <div class="col-md-3">
            <select name="target" class="form-control" required>
                <option value="">Pilih Target</option>
                <option value="siswa" {{ request('target')=='siswa'?'selected':'' }}>Siswa</option>
                <option value="guru" {{ request('target')=='guru'?'selected':'' }}>Guru</option>
            </select>
        </div>

        <div class="col-md-3">
            <select name="kelas" class="form-control" required>
                <option value="">Pilih Kelas</option>
                @foreach(['10','11','12'] as $k)
                    <option value="{{ $k }}" {{ request('kelas')==$k?'selected':'' }}>
                        Kelas {{ $k }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <input type="text" name="tahun" class="form-control"
                   placeholder="2025/2026" value="{{ request('tahun') }}" required>
        </div>

        <div class="col-md-3">
            <button class="btn btn-primary">Tampilkan</button>
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

        @if(request('target') === 'siswa')
        <div class="col-md-3">
            <input type="text" name="rombel"
                class="form-control"
                placeholder="Rombel"
                value="{{ request('rombel') }}"
                required>
        </div>
        @endif

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

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
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