<form id="exportForm" method="POST" action="{{ route('dashboard.export.pdf') }}" target="_blank">
    @csrf
    <input type="hidden" name="mode">
    <input type="hidden" name="year">
    <input type="hidden" name="start_date">
    <input type="hidden" name="end_date">

    <input type="hidden" name="sections">
</form>

<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Export Report</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">

                <div id="exportError" class="alert alert-danger d-none mb-3">
                    Minimal pilih satu bagian laporan.
                </div>

                {{-- MODE EXPORT --}}
                <div class="form-group">
                    <label class="font-weight-bold">Jenis Export</label>

                    <div class="custom-control custom-radio">
                        <input type="radio" id="modeYear" name="exportMode" class="custom-control-input" value="year"
                            checked>
                        <label class="custom-control-label" for="modeYear">Tahunan</label>
                    </div>

                    <div class="custom-control custom-radio">
                        <input type="radio" id="modeRange" name="exportMode" class="custom-control-input" value="range">
                        <label class="custom-control-label" for="modeRange">Rentang Tanggal</label>
                    </div>
                </div>

                {{-- TAHUN --}}
                <div id="yearInput">
                    <label>Tahun</label>
                    <select id="year" class="form-control">
                        @for($y = date('Y'); $y >= 2020; $y--)
                        <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                        @endfor
                    </select>
                </div>

                {{-- RENTANG --}}
                <div id="rangeInput" class="d-none">
                    <div class="form-group">
                        <label>Dari Tanggal</label>
                        <input type="date" id="start_date" class="form-control"
                            value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                    </div>

                    <div class="form-group">
                        <label>Sampai Tanggal</label>
                        <input type="date" id="end_date" class="form-control"
                            value="{{ now()->endOfMonth()->format('Y-m-d') }}">
                    </div>
                </div>

                {{-- PILIH BAGIAN EXPORT --}}
                <div class="form-group mt-3">
                    <label class="font-weight-bold">Bagian Laporan</label>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input export-section" id="secRingkasan"
                            value="ringkasan" checked>
                        <label class="custom-control-label" for="secRingkasan">Ringkasan Umum</label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input export-section" id="secKondisiBuku"
                            value="kondisi_buku" checked>
                        <label class="custom-control-label" for="secKondisiBuku">Kondisi Buku</label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input export-section" id="secSebaranBuku"
                            value="sebaran_buku" checked>
                        <label class="custom-control-label" for="secSebaranBuku">Sebaran Buku per
                            Kategori</label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input export-section" id="secPeminjaman"
                            value="peminjaman" checked>
                        <label class="custom-control-label" for="secPeminjaman">Peminjaman</label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input export-section" id="secPengunjung"
                            value="pengunjung" checked>
                        <label class="custom-control-label" for="secPengunjung">Daftar Pengunjung</label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input export-section" id="secDipinjam"
                            value="buku_dipinjam" checked>
                        <label class="custom-control-label" for="secDipinjam">Daftar Buku yang
                            Dipinjam</label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input export-section" id="secListBuku" value="buku"
                            checked>
                        <label class="custom-control-label" for="secListBuku">Daftar Buku (Baik / Rusak /
                            Hilang)</label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input export-section" id="secListUser"
                            value="list_user" checked>
                        <label class="custom-control-label" for="secListUser">Distribusi User</label>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button class="btn btn-primary" id="confirmExport">Export</button>
            </div>

        </div>
    </div>
</div>