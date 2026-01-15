@extends('layouts.admin')

@section('title', 'Paket Buku')

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

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Paket Buku (Kurikulum)</h1>

        {{-- Tombol tambah --}}
        <a href="{{ route('paket.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Paket
        </a>
    </div>

    {{-- TABLE --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <strong>Daftar Paket Buku</strong>
        </div>

        <div class="card-body">
            <div class="table-responsive overflow-auto" style="max-width:100vw;">
                <table class="table table-bordered table-hover" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Paket</th>
                            <th>Kelas</th>
                            <th>Tahun Ajaran</th>
                            <th>Jumlah Buku</th>
                            <th>Status</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- DUMMY DATA --}}
                        @forelse($pakets ?? [] as $paket)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $paket->nama_paket }}</td>
                                <td>Kelas {{ $paket->kelas }}</td>
                                <td>{{ $paket->tahun_ajaran }}</td>
                                <td>{{ $paket->total_buku ?? 0 }} buku</td>
                                <td>
                                    @if($paket->status_paket === 'aktif')
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('paket.edit', $paket->id) }}" 
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    {{-- Toggle aktif/nonaktif --}}
                                    <form action="{{ route('paket.toggle', $paket->id) }}" 
                                          method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-secondary"
                                            onclick="return confirm('Ubah status paket ini?')">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    Belum ada paket buku.
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