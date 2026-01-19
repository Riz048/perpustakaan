@extends('layouts.admin')

@section('title', 'Data Guru')

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
        <h1 class="h3 mb-0 font-weight-bold text-gray-800">Tabel Guru</h1>

        <div>
            @if(in_array(auth()->user()->role, ['kep_perpus', 'kepsek', 'admin']))
            <button class="btn btn-success shadow-sm" data-toggle="modal" data-target="#modalImportGuru">
                <i class="fas fa-file-import mr-1"></i> Import Guru (Excel)
            </button>
            @endif

            @if(Auth::user()->role != 'kepsek')
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalTambahUser">
                <i class="fas fa-user-plus mr-1"></i> Tambah Guru
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
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>No HP</th>
                            @if(Auth::user()->role != 'kepsek')
                            <th>Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->id_user }}</td>
                            </td>
                            <td>{{ $user->nama }}</td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->telpon }}</td>
                            @if(Auth::user()->role != 'kepsek')
                            <td class="text-center">
                                <button class="btn btn-warning btn-sm" 
                                    data-toggle="modal" 
                                    data-target="#modalEditUser"
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

<div class="modal fade" id="modalTambahUser" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold">Tambah Guru Baru</h5>
                <button class="close text-white" type="button" data-dismiss="modal"><span>&times;</span></button>
            </div>
            
            <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Role</label>
                            <select name="role" class="form-control">
                                <option value="guru">Guru</option>
                            </select>
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
                            <select name="kelamin" class="form-control">
                                <option value="pria">Pria</option>
                                <option value="wanita">Wanita</option>
                            </select>
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
                            <input type="text" id="editIdUser" class="form-control" readonly style="background-color: #e9ecef;">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Role</label>
                            @if(in_array(Auth::user()->role, ['admin','kepsek','kep_perpus']))
                            <select id="editRole" name="role" class="form-control">
                                <option value="guru">Guru</option>
                                <option value="petugas">Petugas</option>
                            </select>
                            @else
                            <input type="hidden" name="role" value="guru">
                            @endif
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

<div class="modal fade" id="modalImportGuru" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold">Import Data Guru (Excel)</h5>
                <button class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-secondary">
                    <strong>Perhatian:</strong>
                    <ul class="mb-0">
                        <li>Gunakan template resmi (jangan ubah header)</li>
                        <li>Kelamin: Pria / Wanita</li>
                    </ul>
                </div>

                <a href="{{ route('template.guru') }}" class="btn btn-success btn-block mb-3">
                    <i class="fas fa-download mr-1"></i> Download Template Excel
                </a>

                <form action="{{ route('users.import.guru') }}" method="POST" enctype="multipart/form-data">
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
    function loadEditUser(
        id, role, username, nama, kelamin, tempat, tanggal, telpon, alamat
    ) {
        var url = "{{ route('users.update', ':id') }}";
        url = url.replace('/0', '/' + id);
        $('#formEditUser').attr('action', "{{ url('/users') }}/" + id);

        // user
        $('#editIdUser').val(id);
        $('#editRole').val(role);
        $('#editUsername').val(username);
        $('#editNama').val(nama);
        $('#editKelamin').val(kelamin);
        $('#editTempatLahir').val(tempat);
        $('#editTanggalLahir').val(tanggal.split(' ')[0]);
        $('#editTelpon').val(telpon);
        $('#editAlamat').val(alamat);
    }

    $(document).ready(function() {
        if ($.fn.DataTable.isDataTable('#dataTable')) {
            $('#dataTable').DataTable().destroy();
        }
        $('#dataTable').DataTable();
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