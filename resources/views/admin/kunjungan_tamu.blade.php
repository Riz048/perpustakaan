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

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-light text-dark text-center border-bottom">
                        <tr>
                            <th class="font-weight-semibold">No</th>
                            <th class="font-weight-semibold">Nama Tamu</th>
                            <th class="font-weight-semibold">Tujuan</th>
                            <th class="font-weight-semibold">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $i => $row)
                        <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
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
