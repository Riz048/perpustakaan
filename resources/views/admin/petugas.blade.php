@extends('layouts.admin')

@section('title', 'Data Petugas')

@section('konten')

<style>
.dataTables_length select {
    position: relative;
    z-index: 1050;
}
</style>

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

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-4 font-weight-bold text-gray-800">Tabel Petugas</h1>
        @if(in_array(Auth::user()->role, ['kepsek', 'kep_perpus', 'admin']))
        <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalTambahPetugas">
            <i class="fas fa-user-plus mr-1"></i> Tambah Petugas
        </button>
        @endif
    </div>

    <div class="card fade-in mb-4">
        <div class="card-body">
            <div class="table-responsive overflow-auto" style="max-width:100vw;">
                <table class="table table-bordered" id="dataTablePetugas" width="100%" cellspacing="0">
                    <thead style="background:#f8f9fc">
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>No Telepon</th>
                            <th>Alamat</th>
                            <th>Level</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($petugas as $p)
                        <tr>
                            <td>{{ $p->id_user }}</td>
                            <td>{{ $p->nama }}</td>
                            <td>{{ $p->username }}</td>
                            <td>{{ $p->telpon }}</td>
                            <td>{{ Str::limit($p->alamat, 20) }}</td>
                            <td>
                                @php
                                    $label = match($p->role) {
                                        'petugas' => 'Petugas',
                                        'admin' => 'Admin',
                                        'kep_perpus' => 'Kepala Perpustakaan',
                                        'kepsek' => 'Kepala Sekolah',
                                        default => ucfirst($p->role),
                                    };
                                @endphp
                                {{ $label }}
                            </td>
                            
                            <td class="text-center">
                                @if(
                                    in_array(Auth::user()->role, ['admin','kepsek','kep_perpus']) ||
                                    (Auth::user()->role === 'kep_perpus' && $p->role === 'petugas')
                                )
                                    <button class="btn btn-warning btn-sm"
                                        data-toggle="modal"
                                        data-target="#modalEditPetugas"
                                        onclick="loadEditPetugas(
                                            '{{ $p->id_user }}',
                                            '{{ $p->nama }}',
                                            '{{ $p->username }}',
                                            '{{ $p->telpon }}',
                                            '{{ $p->alamat }}',
                                            '{{ $p->kelamin }}',
                                            '{{ $p->role }}'
                                        )">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                @else
                                    <span class="text-muted"></span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- {{ $petugas->links() }} --}}
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahPetugas" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Tambah Petugas</h5>
                <button class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('petugas.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Nama Petugas</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>No Telepon</label>
                            <input type="text" name="telpon" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Kelamin</label>
                        <select name="kelamin" class="form-control" required>
                            <option value="pria">Pria</option>
                            <option value="wanita">Wanita</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" class="form-control">
                            <option value="petugas">Petugas</option>

                            @if(in_array(Auth::user()->role, ['kepsek', 'admin', 'kep_perpus']))
                                <option value="admin">Admin</option>
                                <option value="kep_perpus">Kepala Perpustakaan</option>
                                <option value="kepsek">Kepala Sekolah</option>
                            @endif
                        </select>
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

<div class="modal fade" id="modalEditPetugas" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">Edit Petugas</h5>
                <button class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="formEditPetugas" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Nama Petugas</label>
                            <input type="text" id="editNama" name="nama" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Username</label>
                            <input type="text" id="editUsername" name="username" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Password (Isi jika ingin mengganti)</label>
                            <input type="password" name="password" class="form-control" placeholder="******">
                        </div>
                        <div class="form-group col-md-6">
                            <label>No Telepon</label>
                            <input type="text" id="editTelpon" name="telpon" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Kelamin</label>
                        <select id="editKelamin" name="kelamin" class="form-control">
                            <option value="wanita">Wanita</option>
                            <option value="pria">Pria</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea id="editAlamat" name="alamat" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Level / Role</label>
                        <select id="editRole" name="role" class="form-control">
                            <option value="petugas">Petugas</option>
                            <option value="guru">Guru</option>

                            @if(in_array(Auth::user()->role, ['admin','kepsek', 'kep_perpus']))
                                <option value="admin">Admin</option>
                                <option value="kep_perpus">Kepala Perpustakaan</option>
                                <option value="kepsek">Kepala Sekolah</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" id="btnUpdatePetugas" class="btn btn-warning">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    function loadEditPetugas(id, nama, username, telpon, alamat, kelamin, role) {
        console.log(id, nama, username, telpon, alamat, kelamin, role);
        document.getElementById('formEditPetugas').action = `/petugas/${id}`;

        document.getElementById('editNama').value = nama;
        document.getElementById('editUsername').value = username;
        document.getElementById('editTelpon').value = telpon;
        document.getElementById('editAlamat').value = alamat;
        document.getElementById('editKelamin').value = kelamin;
        document.getElementById('editRole').value = role;

        const btnUpdate = document.getElementById('btnUpdatePetugas');

        if (
            "{{ Auth::user()->role }}" === "kep_perpus" &&
            (role === "admin" || role === "kepsek")
        ) {
            document
                .querySelectorAll('#modalEditPetugas input, #modalEditPetugas select')
                .forEach(el => el.disabled = true);

            btnUpdate.style.display = 'none';
        } else {
            btnUpdate.style.display = 'inline-block';
        }
    }

    $('#modalEditPetugas').on('hidden.bs.modal', function () {
        document
            .querySelectorAll('#modalEditPetugas input, #modalEditPetugas select')
            .forEach(el => el.disabled = false);

        document.getElementById('btnUpdatePetugas').style.display = 'inline-block';
    });

    $(document).ready(function () {
        $('#dataTablePetugas').DataTable({
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
            pageLength: 10,
            dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6"f>>rt<"row"<"col-sm-5"i><"col-sm-7"p>>'
        });
    });

    $(document).on('shown.bs.dropdown', '.dropdown', function () {
        $(this).find('.dropdown-menu').appendTo('body');
    });

    $(document).on('hide.bs.dropdown', '.dropdown', function () {
        $(this).find('.dropdown-menu').appendTo(this);
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