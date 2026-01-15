@extends('layouts.admin')
@section('title', 'Peminjaman')

@section('styles')
<style>
    .return-proof { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; cursor: pointer; transition: .2s; border: 1px solid #ddd; }
    .return-proof:hover { transform: scale(1.1); }
    .modal-img { width:100%; border-radius:10px; }
    .filter-select { width: 220px; border-radius:6px; }

    /* Header tabel */
    table.dataTable thead th {
        text-align: center !important;
        vertical-align: middle !important;
    }

    /* Body tabel */
    table.dataTable tbody td {
        text-align: left;
        vertical-align: middle;
    }

.select2-container--default .select2-selection--single {
    height: calc(1.5em + .75rem + 2px);
    padding: .375rem .75rem;
    border: 1px solid #ced4da;
    border-radius: .375rem;
    display: flex;
    align-items: center;
}

.select2-selection__rendered {
    padding-left: 0 !important;
}

.select2-selection__arrow {
    height: 100%;
}

/* Saat dropdown terbuka → selection box dibuat transparan */
.select2-container--open .select2-selection--single {
    border-color: #ced4da;
    background-color: #f8f9fa;
}

/* Sembunyikan teks selection waktu dropdown open */
.select2-container--open .select2-selection__rendered {
    color: transparent;
}

/* Fokus visual ke search input */
.select2-container--open .select2-search__field {
    font-size: 14px;
}
</style>
@endsection

@section('konten')
<div class="container-fluid">
    {{-- NOTIF --}}
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

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-4 font-weight-bold text-gray-800">Tabel Peminjaman</h1>
        @if(Auth::user()->role != 'kepsek')
        <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalTambahPinjam">
            <i class="fas fa-plus mr-1"></i> Tambah Peminjaman
        </button>
        @endif
    </div>

    <div class="card fade-in mb-4">
        <div class="card-body">
            <div class="table-responsive overflow-auto" style="max-width:100vw;">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tanggal Pinjam</th>
                            <th>Lama (Hari)</th>
                            <th>Judul Buku</th>
                            <th>Status</th>
                            <th>Peminjam</th>
                            <th>Petugas</th>
                            <th>Keterangan</th>
                            @if(Auth::user()->role != 'kepsek')
                            <th>Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($peminjaman as $item)
                        <tr>
                            <td data-order="{{ $item->id }}">
                                PMJ-{{ $item->id }}
                            </td>
                            <td>{{ $item->tanggal_pinjam }}</td>
                            <td>{{ $item->lama_pinjam }}</td>
                            <td data-judul="{{ $item->keterangan === 'BUKU_WAJIB'
                                    ? ($item->paket->nama_paket ?? 'Paket Buku Wajib')
                                    : ($item->detail->first()->eksemplar->buku->judul ?? '-') }}">
                                <span style="display:none">
                                    {{ $item->detail->first()->eksemplar->buku->judul ?? '' }}
                                </span>

                                @if($item->keterangan === 'BUKU_WAJIB')
                                    <strong>{{ $item->paket->nama_paket ?? 'Paket Buku Wajib' }}</strong>
                                    <div class="small text-muted">
                                        {{ $item->detail->count() }} buku
                                    </div>
                                @else
                                    {{ $item->detail->first()->eksemplar->buku->judul ?? '-' }}
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $item->status == 'dipinjam' ? 'warning' : 'success' }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td>
                            @if($item->user)
                                @if($item->user->kelasAktif)
                                {{ $item->user->kelasAktif->tingkat }}
                                {{ $item->user->kelasAktif->rombel }} -
                                {{ $item->user->nama }}
                                @else
                                {{ ucfirst($item->user->role) }} -
                                {{ $item->user->nama }}
                                @endif
                            @endif
                            </td>
                            <td>{{ trim($item->petugas->nama ?? '') }}</td>
                            <td>{{ $item->keterangan }}</td>
                            
                            @if(Auth::user()->role != 'kepsek')
                            <td class="text-center">
                                <button class="btn btn-warning btn-sm"
                                    data-toggle="modal"
                                    data-target="#modalEditPinjam"
                                    onclick="loadEditPinjam(
                                        this,
                                        '{{ $item->id }}',
                                        '{{ $item->tanggal_pinjam }}',
                                        '{{ $item->lama_pinjam }}',
                                        '{{ $item->detail->first()->eksemplar->buku_id ?? '' }}',
                                        '{{ $item->status }}',
                                        '{{ $item->id_user }}',
                                        '{{ $item->keterangan }}'
                                    )">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahPinjam">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Tambah Peminjaman</h5><button class="close text-white" data-dismiss="modal">&times;</button></div>
            <form action="{{ route('peminjaman.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Tanggal Peminjaman</label>
                            <input type="date" name="tanggal_pinjam" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Lama Pinjam (Hari)</label>
                            <input type="number" name="lama_pinjam" class="form-control" value="7" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label>Buku</label>
                            <select name="id_buku" class="form-control select-search" required>
                                <option value="">Pilih Buku</option>
                                @foreach($bukus as $bk)
                                    <option value="{{ $bk->id }}">
                                        {{ $bk->judul }} — {{ $bk->pengarang ?? '-' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>User Peminjam</label>
                            <select name="id_user" class="form-control select-search" required>
                                <option value="">Pilih User</option>
                                @foreach($users as $usr)
                                    <option value="{{ $usr->id_user }}">
                                        @if($usr->kelasAktif)
                                            {{ $usr->kelasAktif->tingkat }}
                                            {{ $usr->kelasAktif->rombel }} -
                                        @endif
                                        {{ $usr->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" data-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditPinjam">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white"><h5 class="modal-title">Edit Peminjaman</h5><button class="close text-white" data-dismiss="modal">&times;</button></div>
            <form id="editPinjamForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Tanggal Peminjaman</label>
                            <input type="date" id="eTgl" name="tanggal_pinjam" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Lama (hari)</label>
                            <input type="number" id="eLama" name="lama_pinjam" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label>Buku</label>

                            <input type="text"
                                id="eBukuText"
                                class="form-control"
                                readonly
                                style="background:#f8f9fc">

                            <input type="hidden" name="id_buku" id="eBukuHidden">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Status</label>
                            <input type="text"
                                id="eStatusText"
                                class="form-control"
                                readonly
                                style="background:#f8f9fc;">
                        </div>
                        <div class="form-group col-md-6">
                            <label>User Peminjam</label>

                            <input type="text"
                                id="eUserText"
                                class="form-control"
                                readonly
                                style="background:#f8f9fc">

                            <input type="hidden" name="id_user" id="eUserHidden">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea id="eKet" name="keterangan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" data-dismiss="modal">Batal</button><button class="btn btn-warning text-white">Update</button></div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    function loadEditPinjam(btn, id, tgl, lama, bukuId, status, userId, ket) {
        $('#eTgl').val(tgl);
        $('#eLama').val(lama);

        const row = btn.closest('tr');

        const bukuText = row.querySelector('td:nth-child(4)').dataset.judul;
        const userText = row.querySelector('td:nth-child(6)').innerText.trim();

        $('#eBukuText').val(bukuText);
        $('#eUserText').val(userText);

        $('#eBukuHidden').val(bukuId);
        $('#eUserHidden').val(userId);

        $('#eKet').val(ket);
        $('#eStatusText').val(status);

        $('#editPinjamForm').attr('action', `/peminjaman/${id}`);
    }
    
    $(document).ready(function () { 
        $("#dataTable").DataTable({
            order: [[0, "desc"]],
            columnDefs: [
                {
                    targets: 0,
                    type: "num"
                }
            ],
            search: { smart: false }
        });
    });

    $(document).ready(function () {
        $('.select-search').select2({
            placeholder: 'Ketik untuk mencari buku…',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0
        });
    });

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