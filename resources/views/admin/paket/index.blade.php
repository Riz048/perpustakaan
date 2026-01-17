@extends('layouts.admin')

@section('title', 'Paket Buku')

@section('styles')
<link rel="stylesheet" href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}">
@endsection

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

    {{-- FILTER --}}
    <div class="card mb-4">
        <div class="card-body row">
            <div class="col-md-3">
                <label>Target</label>
                <select id="fTarget" class="form-control">
                    <option value="">Semua</option>
                    <option value="Siswa">Siswa</option>
                    <option value="Guru">Guru</option>
                </select>
            </div>

            <div class="col-md-3">
                <label>Kelas</label>
                <select id="fKelas" class="form-control">
                    <option value="">Semua</option>
                    <option value="10">10</option>
                    <option value="11">11</option>
                    <option value="12">12</option>
                </select>
            </div>

            <div class="col-md-3">
                <label>Rombel</label>
                <select id="fRombel" class="form-control">
                    <option value="">Semua</option>
                </select>
            </div>

            <div class="col-md-3">
                <label>Tahun Ajaran</label>
                <input type="text" id="fTahun" class="form-control" placeholder="2025/2026">
            </div>

            <div class="col-md-12 text-right mt-2">
                <button id="btnReset" class="btn btn-sm btn-secondary px-3">Reset</button>
            </div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <strong>Daftar Paket Buku</strong>
        </div>

        <div class="card-body">
            <div class="table-responsive overflow-auto" style="max-width:100vw;">
                <table class="table table-bordered table-hover" id="paketTable" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th>No</th>
                            <th>Target</th>
                            <th>Nama Paket</th>
                            <th>Kelas</th>
                            <th>Rombel</th>
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
                                <td>
                                    <span style="display:none">{{ ucfirst($paket->target) }}</span>
                                    <span class="badge badge-info">
                                        {{ ucfirst($paket->target) }}
                                    </span>
                                </td>
                                <td>{{ $paket->nama_paket }}</td>
                                <td>
                                    <span style="display:none">{{ $paket->kelas }}</span>
                                    Kelas {{ $paket->kelas }}
                                </td>
                                <td>
                                    @if($paket->target === 'guru')
                                        <span style="display:none">guru</span>
                                        -
                                    @else
                                        <span style="display:none">{{ $paket->kelas }}-{{ $paket->rombel }}</span>
                                        {{ $paket->kelas }}-{{ $paket->rombel }}
                                    @endif
                                </td>
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

@section('script')
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

<script>
    $(document).ready(function () {
        const table = $('#paketTable').DataTable({
            order: [[0, 'asc']],
            search: { smart: false }
        });

        $('#fTarget').on('change', function () {
            table.column(1).search(this.value).draw();

            if (this.value === 'Guru') {
                $('#fRombel').val('').prop('disabled', true);
                table.column(4).search('').draw();
            } else {
                $('#fRombel').prop('disabled', false);
            }
        });

        $('#fKelas').on('change', function () {
            table.column(3).search(this.value).draw();
            buildRombel(this.value);
        });

        $('#fRombel').on('change', function () {
            table.column(4).search(this.value).draw();
        });

        $('#fTahun').on('keyup change', function () {
            table.column(5).search(this.value).draw();
        });

        $('#btnReset').on('click', function () {
            $('#fTarget, #fKelas, #fRombel, #fTahun').val('');
            table.search('').columns().search('').draw();
        });

        function buildRombel(kelas) {
            const rombel = $('#fRombel');
            rombel.html('<option value="">Semua</option>');
            if (!kelas) return;

            for (let i = 1; i <= 9; i++) {
                rombel.append(`<option value="${kelas}-${i}">${kelas}-${i}</option>`);
            }
        }
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
@endsection