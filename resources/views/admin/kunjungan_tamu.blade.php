@extends('layouts.admin')

@section('title','Kunjungan Tamu')

@section('styles')
<style>
.table {
    border-left: 1px solid #e9edf3;
    border-right: 1px solid #e9edf3;
}

.table thead th {
    border-bottom: 2px solid #d1d7e3;
    border-right: 1px solid #e9edf3;
}

.table thead th:last-child {
    border-right: none;
}

.table tbody tr td {
    border-bottom: 1px solid #dfe4ec;
    border-right: 1px solid #e9edf3;
}

.table tbody tr td:last-child {
    border-right: none;
}
</style>
@endsection

@section('konten')
<div class="container-fluid mt-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 font-weight-bold">Daftar Kunjungan {{ now()->year }}</h4>
                <a href="/kunjungan" class="btn btn-secondary px-3">
                    ← Kembali
                </a>
            </div>

            <form method="GET" action="{{ route('kunjungan.tamu') }}" class="mb-3">
                <div class="form-row align-items-end">

                    {{-- FILTER ROLE --}}
                    <div class="col-md-3">
                        <label class="small text-muted">Role</label>
                        <select name="role" class="form-control">
                            <option value="">Semua</option>
                            <option value="siswa" {{ request('role')=='siswa'?'selected':'' }}>Siswa</option>
                            <option value="guru" {{ request('role')=='guru'?'selected':'' }}>Guru</option>
                            <option value="admin" {{ request('role')=='admin'?'selected':'' }}>Admin</option>
                            <option value="kepsek" {{ request('role')=='kepsek'?'selected':'' }}>Kepsek</option>
                            <option value="kep_perpus" {{ request('role')=='kep_perpus'?'selected':'' }}>Kep. Perpus</option>
                            <option value="tamu" {{ request('role')=='tamu'?'selected':'' }}>Tamu</option>
                        </select>
                    </div>

                    {{-- SEARCH NAMA --}}
                    <div class="col-md-4">
                        <label class="small text-muted">Nama</label>
                        <input type="text" name="nama" class="form-control"
                            placeholder="Cari nama pengunjung..."
                            value="{{ request('nama') }}">
                    </div>

                    {{-- FILTER WAKTU --}}
                    <div class="col-md-3">
                        <label class="small text-muted">Periode</label>
                        <select name="periode" class="form-control">
                            <option value="tahun" {{ request('periode')=='tahun'?'selected':'' }}>Tahun ini</option>
                            <option value="bulan" {{ request('periode')=='bulan'?'selected':'' }}>Bulan ini</option>
                            <option value="minggu" {{ request('periode')=='minggu'?'selected':'' }}>Minggu ini</option>
                        </select>
                    </div>

                    {{-- BUTTON --}}
                    <div class="col-md-2 d-flex mt-2">
                        <button class="btn btn-primary flex-fill mr-2">
                            Filter
                        </button>
                        <a href="{{ route('kunjungan.tamu') }}" class="btn btn-outline-secondary flex-fill">
                            Reset
                        </a>
                    </div>
            </form>

            <div class="table-responsive mt-3">
                <table class="table table-hover align-middle">
                    <thead class="bg-light text-dark text-center border-bottom">
                        <tr>
                            <th class="font-weight-semibold">No</th>
                            <th class="font-weight-semibold">Role</th>
                            <th class="font-weight-semibold">Nama Tamu</th>
                            <th class="font-weight-semibold">Tujuan</th>
                            <th class="font-weight-semibold">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $i => $row)
                        <tr>
                            <td class="text-center text-muted small">{{ $i + 1 }}</td>
                            <td class="text-center text-capitalize text-muted">
                                {{ str_replace('_', ' ', $row->role) }}
                            </td>
                            <td>{{ $row->nama_pengunjung }}</td>
                            <td class="text-capitalize">
                                @if ($row->tujuan === 'lainnya')
                                    Lainnya <span class="text-muted">({{ $row->keterangan ?? '-' }})</span>
                                @else
                                    {{ $row->tujuan }}
                                @endif
                            </td>
                            <td class="text-center">
                                {{ \Carbon\Carbon::parse($row->tanggal_kunjungan)->format('d M Y') }}
                            </td>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                Belum ada kunjungan tamu
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
