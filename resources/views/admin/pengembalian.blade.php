@extends('layouts.admin')
@section('title', 'Pengembalian Buku')

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
        <h1 class="h3 mb-4 font-weight-bold text-gray-800">Data Pengembalian Buku</h1>
        @if(Auth::user()->role != 'kepsek')
        <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalTambah">
            <i class="fas fa-plus mr-1"></i> Tambah Pengembalian
        </button>
        @endif
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            
            <div class="mb-3 d-flex justify-content-end">
                <select id="filterStatus" class="form-control filter-select">
                    <option value="">Semua Status</option>
                    <option value="Tepat Waktu">Tepat Waktu</option>
                    <option value="Terlambat">Terlambat</option>
                </select>

                <select id="filterJenis" class="form-control filter-select ml-2">
                    <option value="">Semua Jenis</option>
                    <option value="Buku Wajib">Buku Wajib</option>
                    <option value="Biasa">Biasa</option>
                </select>
            </div>

            <div class="table-responsive overflow-auto" style="max-width:100vw;">
                <table class="table table-bordered" id="tablePengembalian" width="100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Jenis</th>
                            <th>Judul Buku</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Peminjam</th>
                            <th>Status Buku</th>
                            <th>Bukti Foto</th>
                            @if(in_array(Auth::user()->role, ['kep_perpus', 'admin']))
                            <th>Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pengembalian as $index => $item)
                        @php
                            // LOGIKA HITUNG KETERLAMBATAN DI VIEW; Mengambil data tanggal dari relasi peminjaman
                            $tglPinjam = \Carbon\Carbon::parse($item->peminjaman->tanggal_pinjam);
                            $tglKembali = \Carbon\Carbon::parse($item->tanggal_kembali);
                            
                            // Batas waktu = Tgl Pinjam + Lama Pinjam (hari)
                            $batasWaktu = $tglPinjam->copy()->addDays($item->peminjaman->lama_pinjam);
                            
                            // Cek apakah tanggal kembali melebihi batas waktu
                            $isLate = $tglKembali->gt($batasWaktu);
                            $statusLabel = $isLate ? 'Terlambat' : 'Tepat Waktu';
                            $statusBadge = $isLate ? 'danger' : 'success';
                            $diffDays = $isLate ? $tglKembali->diffInDays($batasWaktu) . ' Hari' : '';
                        @endphp

                        <tr>
                            <td data-order="{{ $item->peminjaman_id }}">
                                PMJ-{{ $item->peminjaman_id }}
                            </td>

                        <td>
                            @if($item->peminjaman->keterangan === 'BUKU_WAJIB')
                                <span class="badge badge-info">Buku Wajib</span>
                            @else
                                <span class="badge badge-secondary">Biasa</span>
                            @endif
                        </td>

                            <td>
                                <span style="display:none">
                                    {{ $item->peminjaman->detail->first()->eksemplar->buku->judul ?? '' }}
                                </span>
                                @if($item->peminjaman->keterangan === 'BUKU_WAJIB')
                                    <strong>{{ $item->peminjaman->paket->nama_paket ?? 'Paket Buku' }}</strong>
                                    <div class="small text-muted">
                                        {{ $item->peminjaman->detail->count() }} buku
                                    </div>
                                @else
                                    {{ $item->peminjaman->detail->first()->eksemplar->buku->judul ?? '-' }}
                                @endif
                            </td>

                            <td>{{ $tglKembali->format('d M Y') }}</td>
                            
                            <td>
                                <span class="badge badge-{{ $statusBadge }}">{{ $statusLabel }}</span>
                                @if($isLate) 
                                    <div class="small text-danger mt-1 font-weight-bold">+{{ $diffDays }}</div> 
                                @endif
                            </td>

                            <td>{{ trim($item->user->nama ?? 'User Terhapus') }}</td>
                            
                            <td>
                                @php
                                    $details = $item->peminjaman->detail;
                                @endphp

                                {{-- BUKU PAKET --}}
                                @if($item->peminjaman->keterangan === 'BUKU_WAJIB')
                                    @php
                                        $rusak  = $details->where('kondisi_buku', 'rusak')->count();
                                        $hilang = $details->where('kondisi_buku', 'hilang')->count();
                                    @endphp

                                    @if($rusak || $hilang)
                                        <span class="badge badge-danger">Bermasalah</span>
                                        <div class="small text-danger">
                                            @if($rusak) {{ $rusak }} rusak @endif
                                            @if($hilang) | {{ $hilang }} hilang @endif
                                        </div>
                                    @else
                                        <span class="badge badge-success">Baik</span>
                                    @endif

                                {{-- BUKU BIASA --}}
                                @else
                                    @php
                                        $kondisi = $details->first()->kondisi_buku ?? 'baik';
                                    @endphp

                                    @if($kondisi === 'baik')
                                        <span class="badge badge-success">Baik</span>
                                    @elseif($kondisi === 'rusak')
                                        <span class="badge badge-warning">Rusak</span>
                                    @elseif($kondisi === 'hilang')
                                        <span class="badge badge-danger">Hilang</span>
                                    @endif
                                @endif
                            </td>

                            <td class="text-center">
                                @if($item->foto_bukti)
                                    <img src="{{ asset('storage/' . $item->foto_bukti) }}" 
                                         class="return-proof" 
                                         data-toggle="modal" 
                                         data-target="#modalFoto"
                                         onclick="showFoto('{{ asset('storage/' . $item->foto_bukti) }}')">
                                @else
                                    <span class="badge badge-secondary">No Img</span>
                                @endif
                            </td>

                            @if(in_array(Auth::user()->role, ['kep_perpus', 'admin']))
                            <td class="text-center">

                                @if($item->peminjaman->keterangan === 'BUKU_WAJIB')
                                    {{-- Edit paket --}}
                                    <button class="btn btn-info btn-sm"
                                        data-toggle="modal"
                                        data-target="#modalEditPaket"
                                        onclick="loadEditPaket({{ $item->peminjaman_id }})"
                                        title="Edit paket buku">
                                        <i class="fas fa-box"></i>
                                    </button>
                                @else
                                    {{-- Edit buku biasa --}}
                                    <button class="btn btn-warning btn-sm"
                                        data-toggle="modal"
                                        data-target="#modalEditStatus"
                                        onclick="loadEditStatus({
                                            id: {{ $item->id }},
                                            status: '{{ $item->peminjaman->detail->first()->kondisi_buku ?? 'baik' }}'
                                        })"
                                        title="Edit kondisi buku">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                @endif

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

<div class="modal fade" id="modalFoto">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Bukti Pengembalian</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body p-0">
            <img id="previewFoto" class="modal-img">
        </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalTambah">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Proses Pengembalian Buku</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      
      <form action="{{ route('pengembalian.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="mode_pengembalian" value="biasa">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>

                <script>
                    $(document).ready(function() {
                        $('#modalTambah').modal('show');
                    });
                </script>
            @endif

          <div class="modal-body">
            
            <div class="form-group">
                <label class="font-weight-bold">Pilih Transaksi Peminjaman</label>
                <select name="peminjaman_id" id="addPeminjaman" class="form-control select-peminjaman" required>
                    <option value="">-- Pilih Buku yang Dipinjam --</option>
                    @foreach($peminjamanAktif as $pinjam)
                        <option value="{{ $pinjam->id }}"
                            data-user="{{ $pinjam->user->nama ?? '-' }}"
                            data-tgl="{{ $pinjam->tanggal_pinjam }}"
                            data-lama="{{ $pinjam->lama_pinjam }}"
                            data-keterangan="{{ $pinjam->keterangan }}">

                            @if($pinjam->keterangan === 'BUKU_WAJIB')
                                📦 PMJ-{{ $pinjam->id }}
                                | {{ $pinjam->user->nama }}
                                | {{ $pinjam->paket->nama_paket ?? 'Paket Buku' }}
                                ({{ $pinjam->detail->count() }} buku)
                            @else
                                📘 PMJ-{{ $pinjam->id }}
                                | {{ $pinjam->user->nama }}
                                | {{ $pinjam->detail->first()->eksemplar->buku->judul ?? 'Judul tidak ditemukan' }}
                            @endif
                        </option>
                    @endforeach
                </select>

                <div id="warningPaket" class="alert alert-info d-none mt-2">
                    📦 <strong>Pengembalian Buku Wajib</strong><br>
                    Semua buku dalam paket harus dikembalikan.<br>
                    Setiap buku bisa memiliki kondisi berbeda.
                </div>

                <div id="paketContainer" class="d-none mt-3">
                    <h6 class="font-weight-bold text-primary">Daftar Buku Paket</h6>
                    <div id="listBukuPaket"></div>
                </div>

                <small class="text-muted">Hanya menampilkan peminjaman dengan status "Dipinjam".</small>
            </div>

            <div class="form-group">
                <label class="font-weight-bold">Nama Peminjam</label>
                <input id="addUser" class="form-control" readonly placeholder="Otomatis terisi..." style="background-color: #f8f9fc;">
            </div>

            <div id="infoStatus" class="alert alert-secondary d-none">
                <i class="fas fa-info-circle"></i> Status: <span id="statusText" class="font-weight-bold"></span>
            </div>

            <div class="form-group" id="statusGlobal">
                <label class="font-weight-bold">Status Kondisi Buku</label>
                <select name="status_kondisi" id="statusKondisi" class="form-control">
                    <option value="baik">Baik</option>
                    <option value="rusak">Rusak</option>
                    <option value="hilang">Hilang</option>
                </select>
            </div>

            <div class="form-group">
                <label class="font-weight-bold">Upload Foto Bukti</label>
                <input type="file" name="foto_bukti" id="addFoto" accept="image/*" class="form-control-file"
                    onchange="if(this.files[0] && this.files[0].size > 2097152){ alert('File terlalu besar. Maksimum 2MB.'); this.value=''; document.getElementById('uploadPreview').style.display='none'; } else { previewUpload(event); }">
                <small class="form-text text-muted">Maksimal ukuran file 2MB.</small>
                <div class="mt-2">
                    <img id="uploadPreview" src="#" style="max-height:150px; display:none; border-radius:8px; border:1px solid #ddd;">
                </div>
            </div>

          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan Pengembalian</button>
          </div>
      </form>

    </div>
  </div>
</div>

<div class="modal fade" id="modalEditStatus">
    <div class="modal-dialog">
        <div class="modal-content">

        <div class="modal-header bg-warning text-white">
            <h5 class="modal-title">Edit Status Kondisi Buku</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>

        <form id="formEditStatus" method="POST">
            @csrf
            @method('PUT')

            <div class="modal-body">

                <div class="form-group">
                    <label class="font-weight-bold">Status Kondisi</label>
                    <select name="status_kondisi" id="editStatusSelect" class="form-control">
                        <option value="baik">Baik</option>
                        <option value="rusak">Rusak</option>
                        <option value="hilang">Hilang</option>
                    </select>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
            </div>

        </form>

        </div>
    </div>
</div>

<div class="modal fade" id="modalEditPaket">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">Edit Kondisi Buku Paket</h5>
        <button class="close text-white" data-dismiss="modal">&times;</button>
      </div>

      <form id="formEditPaket" method="POST">
        @csrf
        @method('PUT')

        <div class="modal-body" id="editPaketBody">
            <!-- AJAX -->
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button class="btn btn-info">Simpan Perubahan</button>
        </div>
      </form>

    </div>
  </div>
</div>
@endsection

@section('script')
<script>
$(document).ready(function () {

    // ===== DATATABLE =====
    var table = $('#tablePengembalian').DataTable({
        order: [[0, "desc"]],
        columnDefs: [{ targets: 0, type: 'num' }],
        search: { smart: false }
    });

    $("#filterStatus").on("change", function () {
        table.column(3).search($(this).val()).draw();
    });

    $("#filterJenis").on("change", function () {
        table.column(1).search($(this).val()).draw();
    });

    // ===== SELECT2 PEMINJAMAN =====
    $('#addPeminjaman').select2({
        placeholder: '-- Pilih Buku yang Dipinjam --',
        width: '100%',
        minimumResultsForSearch: 0,
        matcher: function (params, data) {
            if ($.trim(params.term) === '') return data;
            if (!data.text) return null;

            const term = params.term.toLowerCase();
            return data.text.toLowerCase().includes(term)
                ? data
                : null;
        }
    });

    // ===== SATU CHANGE HANDLER SAJA =====
    $("#addPeminjaman").on("change", function () {
        let selected = $(this).find(":selected");

        let user = selected.data("user");
        let tglPinjam = selected.data("tgl");
        let lama = selected.data("lama");
        let ket = selected.data("keterangan");
        let peminjamanId = $(this).val();

        // isi nama user
        $("#addUser").val(user || "");

        // ===== STATUS TERLAMBAT =====
        if (tglPinjam && lama) {
            let datePinjam = new Date(tglPinjam);
            let dateBatas = new Date(datePinjam);
            dateBatas.setDate(datePinjam.getDate() + lama);

            let today = new Date();
            today.setHours(0,0,0,0);
            dateBatas.setHours(0,0,0,0);

            $("#infoStatus").removeClass('d-none');

            if (today > dateBatas) {
                $("#infoStatus")
                    .removeClass('alert-success')
                    .addClass('alert-danger');
                $("#statusText").text("TERLAMBAT");
            } else {
                $("#infoStatus")
                    .removeClass('alert-danger')
                    .addClass('alert-success');
                $("#statusText").text("TEPAT WAKTU");
            }
        } else {
            $("#infoStatus").addClass('d-none');
        }

        // ===== RESET PAKET =====
        $("#listBukuPaket").html('');
        $("#paketContainer").addClass('d-none');
        $("#statusGlobal").removeClass('d-none');
        $("#warningPaket").addClass('d-none');

        // ===== MODE BUKU WAJIB =====
        if (ket === 'BUKU_WAJIB') {
            $('input[name="mode_pengembalian"]').val('paket');
            $("#statusGlobal").addClass('d-none');
            $("#paketContainer").removeClass('d-none');
            $("#warningPaket").removeClass('d-none');

            $.get(`/pengembalian/paket/${peminjamanId}`, function (data) {
                let html = '';
                data.forEach(item => {
                    html += `
                        <div class="form-group border rounded p-2 mb-2">
                            <label class="font-weight-bold">${item.judul}</label>
                            <select name="kondisi[${item.id}]" class="form-control">
                                <option value="baik">Baik</option>
                                <option value="rusak">Rusak</option>
                                <option value="hilang">Hilang</option>
                            </select>
                        </div>
                    `;
                });
                $("#listBukuPaket").html(html);
            });

        } else {
            $('input[name="mode_pengembalian"]').val('biasa');
        }
    });

    $('form[action="{{ route('pengembalian.store') }}"]').on('submit', function (e) {

        const mode = $('input[name="mode_pengembalian"]').val();
        const file = $('#addFoto')[0].files[0];

        let wajibFoto = false;

        // Buku biasa
        if (mode === 'biasa') {
            const status = $('#statusKondisi').val();
            if (status === 'rusak' || status === 'hilang') {
                wajibFoto = true;
            }
        }

        // Buku paket
        if (mode === 'paket') {
            $('select[name^="kondisi"]').each(function () {
                if ($(this).val() === 'rusak' || $(this).val() === 'hilang') {
                    wajibFoto = true;
                }
            });
        }

        // Validasi
        if (wajibFoto && !file) {
            e.preventDefault();
            alert('⚠️ Foto bukti WAJIB diupload jika buku rusak atau hilang.');
            $('#addFoto').focus();
            return false;
        }
    });
});
</script>

<script>
    function loadEditStatus(data) {
        document.getElementById('formEditStatus').action = '/pengembalian/' + data.id;
        document.getElementById('editStatusSelect').value = data.status;
    }

    function loadEditPaket(peminjamanId) {
        $('#editPaketBody').html('Loading...');
        $('#formEditPaket').attr('action', '/pengembalian/paket/' + peminjamanId);

        $.get('/pengembalian/paket/edit/' + peminjamanId, function (data) {
            let html = '';
            data.forEach(item => {
                html += `
                    <div class="form-group border rounded p-2 mb-2">
                        <label class="font-weight-bold">${item.judul}</label>
                        <select name="kondisi[${item.id}]" class="form-control">
                            <option value="baik" ${item.kondisi==='baik'?'selected':''}>Baik</option>
                            <option value="rusak" ${item.kondisi==='rusak'?'selected':''}>Rusak</option>
                            <option value="hilang" ${item.kondisi==='hilang'?'selected':''}>Hilang</option>
                        </select>
                    </div>
                `;
            });
            $('#editPaketBody').html(html);
        });
    }
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
