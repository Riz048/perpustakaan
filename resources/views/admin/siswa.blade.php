@extends('layouts.admin')

@section('title', 'Data Siswa')

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

    @if(session('error_import'))
    <div id="pageAlert" class="alert alert-warning">
        <strong>Beberapa data gagal diimport:</strong><br>
        @foreach(session('error_import') as $err)
        {{ $err }}<br>
        @endforeach
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0 font-weight-bold text-gray-800">Tabel Siswa</h1>

        <div>
            @if(in_array(auth()->user()->role, ['kep_perpus', 'kepsek', 'admin']))
            <button class="btn btn-success shadow-sm" data-toggle="modal" data-target="#modalImportSiswa">
                <i class="fas fa-file-import mr-1"></i> Import Siswa (Excel)
            </button>
            @endif

            @if(Auth::user()->role != 'kepsek')
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalTambahUser">
                <i class="fas fa-user-plus mr-1"></i> Tambah Siswa
            </button>
            @endif
        </div>
    </div>

    <div class="card fade-in mb-4">
        <div class="card-body">
            <div class="table-responsive overflow-auto" style="max-width:100vw;">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead style="background: #f8f9fc;">
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Kelas</th>
                            <th>No HP</th>
                            @if(Auth::user()->role != 'kepsek')
                            <th>Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $user->nama }}</td>
                            <td>{{ $user->username }}</td>
                            <td>{{ optional($user->kelasAktif)->tingkat }}-{{ optional($user->kelasAktif)->rombel }}
                            </td>
                            <td>{{ $user->telpon }}</td>
                            @if(Auth::user()->role != 'kepsek')
                            <td class="text-center">
                                <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEditUser"
                                    onclick="loadEditUser(
                                        '{{ $user->id_user }}',
                                        '{{ $user->role }}',
                                        '{{ $user->username }}',
                                        '{{ $user->nama }}',
                                        '{{ $user->kelamin }}',
                                        '{{ $user->tempat_lahir }}',
                                        '{{ $user->tanggal_lahir }}',
                                        '{{ $user->telpon }}',
                                        '{{ $user->alamat }}',
                                        '{{ optional($user->kelasAktif)->tingkat }}',
                                        '{{ optional($user->kelasAktif)->rombel }}',
                                        '{{ optional($user->kelasAktif)->tahun_ajaran }}',
                                        '{{ optional($user->kelasAktif)->semester }}'
                                    )">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('users.nonaktif', $user->id_user) }}"
                                    method="POST"
                                    style="display:inline"
                                    onsubmit="return confirm('Yakin menonaktifkan siswa ini?')">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-danger btn-sm">
                                        <i class="fas fa-user-slash"></i>
                                    </button>
                                </form>
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

<div class="modal fade" id="modalTambahUser" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold">Tambah Siswa Baru</h5>
                <button class="close text-white" type="button" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Role</label>
                            <input type="text" class="form-control" value="Siswa" readonly>
                            <input type="hidden" name="role" value="siswa">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Kelamin</label>
                            <select name="kelamin" class="form-control" required>
                                <option value="pria">Pria</option>
                                <option value="wanita">Wanita</option>
                            </select>
                        </div>
                    </div>

                    <div id="formKelasSiswa">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Tingkat</label>
                                <select name="tingkat" id="tingkatSiswa" class="form-control" required>
                                    <option value="">Pilih Tingkat</option>
                                    <option value="10">10</option>
                                    <option value="11">11</option>
                                    <option value="12">12</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Rombel</label>
                                <select name="rombel" id="rombelSiswa" class="form-control" required disabled>
                                    <option value="">Pilih Rombel</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Tahun Ajaran</label>
                                <input type="text" name="tahun_ajaran" class="form-control" placeholder="2024/2025"
                                    required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Semester</label>
                                <select name="semester" class="form-control" required>
                                    <option value="ganjil">Ganjil</option>
                                    <option value="genap">Genap</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label>No Telepon</label>
                            <input type="text" name="telpon" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Upload Foto</label>
                        <input type="file" name="foto" class="form-control" accept="image/*"
                            onchange="if(this.files[0] && this.files[0].size > 2097152){ alert('File terlalu besar. Maksimum 2MB.'); this.value=''; document.getElementById('uploadPreview').style.display='none'; } else { previewUpload(event); }">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditUser" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title font-weight-bold">Edit User</h5>
                <button class="close text-white" type="button" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form id="formEditUser" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>ID Anggota</label>
                            <input type="text" id="editIdUser" class="form-control" readonly
                                style="background-color: #e9ecef;">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Role</label>
                            <input type="text" class="form-control" value="Siswa" readonly>
                            <input type="hidden" name="role" value="siswa">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Username</label>
                            <input type="text" name="username" id="editUsername" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Password (Isi jika ingin ubah)</label>
                            <input type="password" name="password" class="form-control" placeholder="******">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama" id="editNama" class="form-control" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Kelamin</label>
                            <select name="kelamin" id="editKelamin" class="form-control">
                                <option value="pria">Pria</option>
                                <option value="wanita">Wanita</option>
                            </select>
                        </div>
                    </div>

                    <div id="editFormKelasSiswa" style="display:none;">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Tingkat</label>
                                <select name="tingkat" id="editTingkat" class="form-control" required>
                                    <option value="">Pilih Tingkat</option>
                                    <option value="10">10</option>
                                    <option value="11">11</option>
                                    <option value="12">12</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Rombel</label>
                                <select name="rombel" id="editRombel" class="form-control" required>
                                    <option value="">Pilih Rombel</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Tahun Ajaran</label>
                                <input type="text" name="tahun_ajaran" id="editTahunAjaran" class="form-control"
                                    required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Semester</label>
                                <select name="semester" id="editSemester" class="form-control" required>
                                    <option value="ganjil">Ganjil</option>
                                    <option value="genap">Genap</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" id="editTempatLahir" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" id="editTanggalLahir" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label>No Telepon</label>
                            <input type="text" name="telpon" id="editTelpon" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Alamat Lengkap</label>
                        <textarea name="alamat" id="editAlamat" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Ganti Foto (Opsional)</label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-white">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalImportSiswa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold">Import Data Siswa (Excel)</h5>
                <button class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-secondary">
                    <strong>Perhatian:</strong>
                    <ul class="mb-0">
                        <li>Gunakan template resmi (jangan ubah header)</li>
                        <li>Kelamin: Pria / Wanita</li>
                        <li>Tingkat: 10, 11, atau 12</li>
                        <li>Rombel: angka 1–9</li>
                        <li>Format Tahun Ajaran: 2024/2025</li>
                        <li>Semester: Ganjil / Genap</li>
                    </ul>
                </div>

                <a href="{{ route('template.siswa') }}" class="btn btn-success btn-block mb-3">
                    <i class="fas fa-download mr-1"></i> Download Template Excel
                </a>

                <form action="{{ route('users.import.siswa') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>Upload File Excel (.xlsx)</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx" required>
                        <small class="text-muted">
                            Gunakan template resmi. Jangan ubah nama kolom.
                        </small>
                    </div>

                    <button class="btn btn-info btn-block text-white">
                        <i class="fas fa-upload mr-1"></i> Import Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
const rombelMap = {
    10: [1, 2, 3, 4, 5, 6, 7, 8, 9],
    11: [1, 2, 3, 4, 5, 6, 7, 8, 9],
    12: [1, 2, 3, 4, 5, 6, 7, 8, 9],
};

// CREATE
const tingkatSiswa = document.getElementById('tingkatSiswa');
if (tingkatSiswa) {
    tingkatSiswa.addEventListener('change', function() {
        fillRombel(this.value, 'rombelSiswa');
    });
}

// EDIT
const editTingkat = document.getElementById('editTingkat');
if (editTingkat) {
    editTingkat.addEventListener('change', function() {
        populateEditRombel(this.value);
    });
}

function fillRombel(tingkat, targetId) {
    const select = document.getElementById(targetId);
    select.innerHTML = '<option value="">Pilih Rombel</option>';
    if (!tingkat) return;

    rombelMap[tingkat].forEach(r => {
        const opt = document.createElement('option');
        opt.value = r;
        opt.textContent = `${tingkat}-${r}`;
        select.appendChild(opt);
    });

    select.disabled = false;
}

function populateEditRombel(tingkat, rombelAktif = null) {
    const select = document.getElementById('editRombel');
    select.innerHTML = '<option value="">Pilih Rombel</option>';
    if (!tingkat) return;

    rombelMap[tingkat].forEach(r => {
        const opt = document.createElement('option');
        opt.value = r;
        opt.textContent = `${tingkat}-${r}`;
        if (rombelAktif && r == rombelAktif) opt.selected = true;
        select.appendChild(opt);
    });
}
</script>

<script>
function loadEditUser(
    id, role, username, nama, kelamin, tempat, tanggal, telpon, alamat, tingkat, rombel, tahun, semester
) {
    var url = "{{ route('users.update', ':id') }}";
    url = url.replace('/0', '/' + id);
    $('#formEditUser').attr('action', "{{ url('/users') }}/" + id);

    // user
    $('#editIdUser').val(id);
    $('#editUsername').val(username);
    $('#editNama').val(nama);
    $('#editKelamin').val(kelamin);
    $('#editTempatLahir').val(tempat);
    $('#editTanggalLahir').val(tanggal ? tanggal.split(' ')[0] : '');
    $('#editTelpon').val(telpon);
    $('#editAlamat').val(alamat);
    $('#editTingkat').val(tingkat);
    populateEditRombel(tingkat, rombel);
    $('#editTahunAjaran').val(tahun);
    $('#editSemester').val(semester);

    // kelas siswa
    if (role === 'siswa') {
        $('#editFormKelasSiswa').show();
    } else {
        $('#editFormKelasSiswa').hide();
    }
}

$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#dataTable')) {
        $('#dataTable').DataTable().destroy();
    }
    $('#dataTable').DataTable();
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