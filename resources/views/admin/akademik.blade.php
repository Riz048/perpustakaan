@extends('layouts.admin')
@section('title', 'Buku Akademik')

@section('konten')
<!-- TOPBAR (SAMA PERSIS NONAKADEMIK) -->
{{-- <nav class="navbar navbar-expand navbar-light topbar mb-4 shadow">
  <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
    <i class="fa fa-bars"></i>
  </button>
  <ul class="navbar-nav ml-auto">
    <li class="nav-item dropdown no-arrow">
      <a class="nav-link"><span class="mr-2 d-none d-lg-inline text-gray-600 small">Petugas</span></a>
    </li>
  </ul>
</nav> --}}

<!-- PAGE CONTENT  -->
<div class="container-fluid">

    @if (session('success'))
    <div id="pageAlert" class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-1"></i>
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    @if ($errors->any())
    <div id="pageAlert" class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $errors->first() }}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
    @endif

    @if(session('error_import'))
    <div id="pageAlert" class="alert alert-warning">
        <strong>Beberapa data gagal diimport:</strong><br>
        @foreach(session('error_import') as $err)
        {{ $err }}<br>
        @endforeach
    </div>
    @endif
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0 font-weight-bold text-gray-800">
            Data Buku Akademik
        </h1>

        <div>
            @if(in_array(auth()->user()->role, ['kep_perpus', 'kepsek', 'admin']))
            <button class="btn btn-success shadow-sm mr-2" data-toggle="modal" data-target="#modalImportBukuAkademik">
                <i class="fas fa-file-import mr-1"></i>
                Import Buku (Excel)
            </button>
            @endif

            @if(Auth::user()->role != 'kepsek')
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalTambahAkad">
                <i class="fas fa-plus mr-1"></i>
                Tambah Buku
            </button>
            @endif
        </div>
    </div>

    <!-- TABLE -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive overflow-auto" style="max-width:100vw;">

                <table id="tabelAkademik" class="table table-bordered" width="100%">
                    <thead style="background:#f2f2f2; color:#000;">
                        <tr>
                            <th>No</th>
                            <th>Kategori</th>
                            <th>Kode Buku</th>
                            <th>Judul</th>
                            <th>Penerbit</th>
                            <th>ISBN</th>
                            <th>Pengarang</th>
                            <th>Buku Masuk</th>
                            <th>Dipinjam</th>
                            <th>Tersedia</th>
                            <th>Tahun Masuk</th>
                            <th>Sinopsis</th>
                            @if(Auth::user()->role != 'kepsek')
                            <th>Aksi</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($buku as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>Kelas {{ $item->kelas_akademik }}</td>
                            <td>{{ $item->kode_buku }}</td>
                            <td>{{ $item->judul }}</td>
                            <td>{{ $item->nama_penerbit }}</td>
                            <td>{{ $item->isbn }}</td>
                            <td>{{ $item->pengarang }}</td>
                            <td><strong>{{ $item->buku_masuk }}</strong></td>
                            <td><span class="badge badge-warning">{{ $item->buku_dipinjam }}</span></td>
                            <td><span class="badge badge-success">{{ $item->buku_tersedia }}</span></td>
                            <td>{{ $item->tahun_masuk }}</td>
                            <td>{{ Str::limit($item->sinopsis, 20) }}</td>

                            @if(Auth::user()->role != 'kepsek')
                            <td class="text-center">
                                <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEditAkad"
                                    onclick="loadEditAkad(
                                    '{{ $item->id }}',
                                    '{{ $item->kelas_akademik }}',
                                    '{{ $item->tipe_bacaan }}',
                                    '{{ $item->kode_buku }}',
                                    '{{ $item->judul }}',
                                    '{{ $item->nama_penerbit }}',
                                    '{{ $item->isbn }}',
                                    '{{ $item->pengarang }}',
                                    '{{ $item->jumlah_baik }}',
                                    '{{ $item->jumlah_rusak }}',
                                    '{{ $item->jumlah_hilang }}',
                                    '{{ $item->tahun_terbit }}',
                                    '{{ $item->tahun_masuk }}',
                                    '{{ $item->sinopsis }}',
                                    '{{ $item->keterangan }}',
                                    '{{ $item->gambar }}'
                                )">
                                    <i class="fas fa-edit"></i>
                                </button>

                                @if(in_array(Auth::user()->role, ['kep_perpus', 'admin']))
                                <form action="{{ route('buku.destroy', $item->id) }}" method="POST"
                                    style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Yakin hapus?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
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

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambahAkad">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Tambah Buku Akademik</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('buku.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">

                        <div class="col-md-6 mb-2">
                            Kategori:
                            <select name="kelas_akademik" class="form-control" required>
                                <option value="">Pilih Kategori</option>
                                <option value="10">Kelas 10</option>
                                <option value="11">Kelas 11</option>
                                <option value="12">Kelas 12</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-2">
                            Kode Buku:
                            <input type="text" name="kode_buku" class="form-control" placeholder="Contoh: BIO-10"
                                required>
                        </div>

                        <div class="col-md-8 mb-2">
                            Judul Buku:
                            <input type="text" name="judul" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-2">
                            Penerbit:
                            <input type="text" name="nama_penerbit" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-2">
                            ISBN:
                            <input type="text" name="isbn" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-2">
                            Pengarang:
                            <input type="text" name="pengarang" class="form-control" required>
                        </div>

                        <div class="col-md-4 mb-2">
                            Total:
                            <input type="number" id="tTotal" class="form-control" readonly>
                        </div>

                        <div class="col-md-4 mb-2">
                            Baik:
                            <input type="number" name="stok_baik" id="tStokBaik" class="form-control" value="0" min="0">
                        </div>

                        <div class="col-md-4 mb-2">
                            Rusak:
                            <input type="number" name="stok_rusak" id="tStokRusak" class="form-control" value="0"
                                min="0">
                        </div>

                        <div class="col-md-4 mb-2">
                            Hilang:
                            <input type="number" name="stok_hilang" id="tStokHilang" class="form-control" value="0"
                                min="0">
                        </div>

                        <div class="col-md-4 mb-2">
                            Tahun Terbit:
                            <input type="number" name="tahun_terbit" class="form-control" placeholder="YYYY" min="1900"
                                max="2099" required>
                        </div>

                        <div class="col-md-4 mb-2">
                            Tahun Masuk:
                            <input type="number" name="tahun_masuk" class="form-control" placeholder="YYYY" min="1900"
                                max="2099" required>
                        </div>

                        <div class="col-md-12 mb-2">
                            Sinopsis:
                            <textarea name="sinopsis" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="col-md-12 mb-2">
                            Keterangan:
                            <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Asal buku">
                        </div>

                        <div class="col-md-12 mb-2">
                            Gambar Cover:
                            <input type="file" name="gambar" accept="image/*" class="form-control-file"
                                onchange="previewAddImage(event)">
                            <div class="mt-2">
                                <img id="addPreview" src="#" alt="Preview"
                                    style="max-height:180px; display:none; border:1px solid #ddd; padding:4px; border-radius:4px;">
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Buku</button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- MODAL EDIT -->
<div class="modal fade" id="modalEditAkad">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5>Edit Buku Akademik</h5>
            </div>

            <form id="editAkadForm" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="eId" name="id">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            Kategori:
                            <select id="eKategori" name="kelas_akademik" class="form-control">
                                <option value="">Pilih Kategori</option>
                                <option value="10">Kelas 10</option>
                                <option value="11">Kelas 11</option>
                                <option value="12">Kelas 12</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            Tipe:
                            <select id="eTipe" name="tipe_bacaan" class="form-control">
                                <option value="">Pilih Tipe</option>
                                <option value="fiksi">Fiksi</option>
                                <option value="non-fiksi">Non-Fiksi</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">Kode Buku:<input id="eKode" name="kode_buku" class="form-control">
                        </div>
                        <div class="col-md-8 mb-2">Judul:<input id="eJudul" name="judul" class="form-control"></div>
                        <div class="col-md-6 mb-2">Penerbit:<input id="ePenerbit" name="nama_penerbit"
                                class="form-control"></div>
                        <div class="col-md-6 mb-2">ISBN:<input id="eIsbn" name="isbn" class="form-control"></div>
                        <div class="col-md-6 mb-2">Pengarang:<input id="ePengarang" name="pengarang"
                                class="form-control"></div>
                        <div class="col-md-4 mb-2">
                            Total:
                            <input type="number" id="eTotal" class="form-control" readonly>
                        </div>
                        <div class="col-md-4 mb-2">
                            Baik:
                            <input type="number" name="stok_baik" id="eStokBaik" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-4 mb-2">
                            Rusak:
                            <input type="number" name="stok_rusak" id="eStokRusak" class="form-control" value="0"
                                min="0">
                        </div>
                        <div class="col-md-4 mb-2">
                            Hilang:
                            <input type="number" name="stok_hilang" id="eStokHilang" class="form-control" value="0"
                                min="0">
                        </div>
                        <div class="col-md-4 mb-2">Tahun Terbit:<input type="number" id="eTahunTerbit"
                                name="tahun_terbit" class="form-control"></div>
                        <div class="col-md-4 mb-2">Tahun Masuk:<input type="number" id="eTahunMasuk" name="tahun_masuk"
                                class="form-control"></div>
                        <div class="col-md-12 mb-2">Sinopsis:<textarea id="eSinopsis" name="sinopsis"
                                class="form-control" rows="3"></textarea></div>
                        <div class="col-md-12 mb-2">
                            Keterangan:
                            <input type="text" id="eKeterangan" name="keterangan" class="form-control">
                        </div>
                        <div class="col-md-12 mb-2">
                            Gambar:
                            <input type="file" id="eGambar" name="gambar" accept="image/*" class="form-control-file"
                                onchange="previewEditImage(event)">
                            <div class="mt-2">
                                <img id="ePreview" src="#" alt="Preview Gambar"
                                    style="max-height:180px; display:none; border:1px solid #ddd; padding:4px; border-radius:4px;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button class="btn btn-warning" type="submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalImportBukuAkademik" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold">Import Data Buku Akademik (Excel)</h5>
                <button class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Perhatian:</strong>
                    <ul class="mb-0 small">
                        <li>Gunakan template resmi (jangan ubah nama kolom)</li>
                        <li><strong>Kelas Akademik:</strong> hanya 10, 11, atau 12</li>
                        <li><strong>Tahun:</strong> format YYYY (contoh: 2024)</li>
                        <li><strong>Stok:</strong> angka ≥ 0</li>
                    </ul>
                </div>

                <a href="{{ route('template.akademik') }}" class="btn btn-success btn-block mb-3">
                    <i class="fas fa-download mr-1"></i> Download Template Excel
                </a>

                <form action="{{ route('buku.import.akademik') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>Upload File Excel (.xlsx)</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx" required>
                        <small class="text-muted">
                            Gunakan template resmi. Jangan ubah nama kolom.
                        </small>
                    </div>

                    <button class="btn btn-info btn-block text-white"
                            type="submit"
                            id="btnImportBukuAkademik">
                        <i class="fas fa-upload mr-1"></i>
                        Import Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>

@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formImportBukuAkademik');
    const btn  = document.getElementById('btnImportBukuAkademik');

    if (!form || !btn) return;

    form.addEventListener('submit', function () {
        btn.disabled = true;
        btn.innerHTML =
          '<i class="fas fa-spinner fa-spin mr-1"></i> Mengimpor...';
    });
});
</script>

<script>
function loadEditAkad(
    id, kategori, tipe, kode, judul, penerbit, isbn,
    pengarang, baik, rusak, hilang, tahun_terbit, tahun_masuk, sinopsis, keterangan, gambar
) {
    // $('#modalEditAkad').off('shown.bs.modal').on('shown.bs.modal', function () {

    $('#eId').val(id);
    $('#eKategori').val(kategori);
    $('#eTipe').val(tipe);
    $('#eKode').val(kode);
    $('#eJudul').val(judul);
    $('#ePenerbit').val(penerbit);
    $('#eIsbn').val(isbn);
    $('#ePengarang').val(pengarang);

    $('#eStokBaik').val(parseInt(baik) || 0);
    $('#eStokRusak').val(parseInt(rusak) || 0);
    $('#eStokHilang').val(parseInt(hilang) || 0);

    $('#eTotal').val(
        (parseInt(baik) || 0) +
        (parseInt(rusak) || 0) +
        (parseInt(hilang) || 0)
    );

    $('#eTahunTerbit').val(tahun_terbit);
    $('#eTahunMasuk').val(tahun_masuk);
    $('#eSinopsis').val(sinopsis);
    $('#eKeterangan').val(keterangan);
    $('#editAkadForm').attr('action', `/buku/${id}`);

    const img = document.getElementById('ePreview');
    if (gambar) {
        img.src = `/storage/${gambar}`;
        img.style.display = 'block';
    } else {
        img.style.display = 'none';
    }
    // });
}

function previewAddImage(event) {
    var reader = new FileReader();
    reader.onload = function() {
        var output = document.getElementById('addPreview');
        output.src = reader.result;
        output.style.display = 'block';
    };
    reader.readAsDataURL(event.target.files[0]);
}

function previewEditImage(event) {
    const input = event.target;
    const file = input.files && input.files[0];
    const img = document.getElementById('ePreview');
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        img.src = '#';
        img.style.display = 'none';
    }
}
</script>

<script>
$(document).ready(function() {
    $('#tabelAkademik').DataTable({
        pageLength: window.innerWidth < 768 ? 5 : 10,
        lengthChange: window.innerWidth >= 768,
        responsive: true,

        columnDefs: [{
            targets: 0,
            searchable: false
        }]
    });
});
</script>

<script>
// tambah
function hitungTotalTambah() {
    const baik = parseInt($('#tStokBaik').val()) || 0;
    const rusak = parseInt($('#tStokRusak').val()) || 0;
    const hilang = parseInt($('#tStokHilang').val()) || 0;
    $('#tTotal').val(baik + rusak + hilang);
}

$('#tStokBaik, #tStokRusak, #tStokHilang').on('input', hitungTotalTambah);

// edit
function hitungTotalEdit() {
    const baik = parseInt($('#eStokBaik').val()) || 0;
    const rusak = parseInt($('#eStokRusak').val()) || 0;
    const hilang = parseInt($('#eStokHilang').val()) || 0;
    $('#eTotal').val(baik + rusak + hilang);
}

$('#eStokBaik, #eStokRusak, #eStokHilang').on('input', hitungTotalEdit);
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