@extends('layouts.admin')

@section('title', 'Dashboard Perpustakaan')

@section('styles')
<style>
    .stat-number { color: var(--primary); font-weight: 700; font-size: 1.25rem; }
    .chart-container { min-height: 240px; height: 100%; position: relative; }
    
    .card { border: 0; border-radius: .8rem; box-shadow: var(--card-shadow); }
    .card .card-header { background: transparent; border-bottom: 0; font-weight:600; color:var(--secondary); }
    .card .card-body { padding: 1.25rem; }

</style>
@endsection

@section('konten')
<div class="container-fluid fade-in">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div class="flex items-center">

            {{-- Tombol Kembali hanya untuk Staff --}}
            @if(in_array(auth()->user()->role, ['petugas', 'kep_perpus', 'kepsek', 'admin']))
                <a href="{{ url('/') }}" class="btn btn-sm btn-secondary shadow-sm mr-2">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            @endif

            {{-- Tombol Export hanya untuk Kepperpus & Kepsek --}}
            @if(in_array(auth()->user()->role, ['kep_perpus', 'kepsek', 'admin']))
                <button class="btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#exportModal">
                    <i class="fas fa-download fa-sm text-white-50"></i> Export Report
                </button>
            @endif

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

                            <div id="exportError"
                                class="alert alert-danger d-none mb-3">
                                Minimal pilih satu bagian laporan.
                            </div>

                            {{-- MODE EXPORT --}}
                            <div class="form-group">
                                <label class="font-weight-bold">Jenis Export</label>

                                <div class="custom-control custom-radio">
                                    <input type="radio" id="modeYear" name="exportMode" class="custom-control-input" value="year" checked>
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
                                    <input type="checkbox" class="custom-control-input export-section" id="secRingkasan" value="ringkasan" checked>
                                    <label class="custom-control-label" for="secRingkasan">Ringkasan Umum</label>
                                </div>

                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input export-section" id="secKondisiBuku" value="kondisi_buku" checked>
                                    <label class="custom-control-label" for="secKondisiBuku">Kondisi Buku</label>
                                </div>

                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input export-section" id="secSebaranBuku" value="sebaran_buku" checked>
                                    <label class="custom-control-label" for="secSebaranBuku">Sebaran Buku per Kategori</label>
                                </div>

                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input export-section" id="secPeminjaman" value="peminjaman" checked>
                                    <label class="custom-control-label" for="secPeminjaman">Peminjaman</label>
                                </div>

                                <hr>

                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input export-section" id="secGrafikBuku" value="grafik_buku" checked>
                                    <label class="custom-control-label" for="secGrafikBuku">Grafik Buku</label>
                                </div>

                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input export-section" id="secGrafikUser" value="grafik_user" checked>
                                    <label class="custom-control-label" for="secGrafikUser">Grafik Komposisi User</label>
                                </div>

                                <hr>

                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input export-section" id="secListBuku" value="buku" checked>
                                    <label class="custom-control-label" for="secListBuku">Daftar Buku (Baik / Rusak / Hilang)</label>
                                </div>

                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input export-section" id="secListUser" value="list_user" checked>
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

        </div>
    </div>

    <div class="row">

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1 text-muted">Buku Akademik</div>
                            <div class="h5 mb-0 font-weight-bold stat-number">{{ number_format($totalAkademik) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book-reader fa-2x text-gray-600"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1 text-muted">Total Buku Bacaan</div>
                            <div class="h5 mb-0 font-weight-bold stat-number">{{ number_format($totalNonAkademik) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book-open fa-2x text-gray-600"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1 text-muted">Total Siswa</div>
                            <div class="h5 mb-0 font-weight-bold stat-number">{{ number_format($totalSiswa) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-graduate fa-2x text-gray-600"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1 text-muted">Total Pegawai</div>
                            <div class="h5 mb-0 font-weight-bold stat-number">{{ number_format($totalPegawai) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-gray-600"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12 col-lg-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Grafik Peminjaman Tahun {{ date('Y') }}</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartPeminjamanBulan"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-7 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistik Stok Buku (Eksemplar)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartStokBuku"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Komposisi User</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="chartUser"></canvas>
                    </div>
                    <div class="mt-4 text-center small text-muted">
                        Distribusi pengguna berdasarkan role
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Aktivitas Terbaru</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        @forelse($recentActivities as $activity)
                            <li class="mb-3 border-bottom pb-2">
                                <span class="text-dark font-weight-bold">{{ $activity->user->nama ?? 'User Hapus' }}</span>
                                <span class="float-right text-muted small">{{ \Carbon\Carbon::parse($activity->tanggal_pinjam)->diffForHumans() }}</span>
                                <br>
                                <span class="badge badge-{{ $activity->status == 'dipinjam' ? 'warning' : 'success' }}">
                                    {{ ucfirst($activity->status) }}
                                </span>
                                <small class="ml-2">{{ $activity->keterangan ?? 'Meminjam Buku' }}</small>
                            </li>
                        @empty
                            <li class="text-center text-muted">Belum ada aktivitas.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Buku Terbaru</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive overflow-auto" style="max-width:100vw;">
                        <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Kategori</th>
                                    <th>Stok</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bukuTerbaru as $buku)
                                <tr>
                                    <td>{{ Str::limit($buku->judul, 30) }}</td>
                                    <td>
                                        @if($buku->kelas_akademik == 'non-akademik')
                                            <span class="badge badge-info">Umum</span>
                                        @else
                                            <span class="badge badge-primary">Kls {{ $buku->kelas_akademik }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $buku->jlh_buku }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

</div>
@endsection

@section('script')
<script src="{{ asset('vendor/chart.js/Chart.min.js') }}"></script>

<script>
    const modeYear  = document.getElementById('modeYear');
    const modeRange = document.getElementById('modeRange');
    const yearInput = document.getElementById('yearInput');
    const rangeInput = document.getElementById('rangeInput');

    function toggleMode() {
        if (modeYear.checked) {
            yearInput.classList.remove('d-none');
            rangeInput.classList.add('d-none');
        } else {
            yearInput.classList.add('d-none');
            rangeInput.classList.remove('d-none');
        }
    }

    modeYear.addEventListener('change', toggleMode);
    modeRange.addEventListener('change', toggleMode);
    toggleMode();

document.getElementById('confirmExport').addEventListener('click', function () {

    const errorBox = document.getElementById('exportError');
    errorBox.classList.add('d-none');

    // ========================
    // SECTIONS
    // ========================
    const sections = [];
    document.querySelectorAll('.export-section:checked').forEach(el => {
        sections.push(el.value);
    });

    if (sections.length === 0) {
        errorBox.classList.remove('d-none');
        return;
    }

    document.querySelector('[name="sections"]').value = JSON.stringify(sections);

    // ========================
    // MODE
    // ========================
    if (modeYear.checked) {
        document.querySelector("[name=mode]").value = 'year';
        document.querySelector("[name=year]").value =
            document.getElementById('year').value;
    } else {
        document.querySelector("[name=mode]").value = 'range';
        document.querySelector("[name=start_date]").value =
            document.getElementById('start_date').value;
        document.querySelector("[name=end_date]").value =
            document.getElementById('end_date').value;
    }

    const form = document.getElementById('exportForm');

    if (sections.includes('grafik_peminjaman')) {
        let img = document.getElementById('chartPeminjamanBulan').toDataURL('image/png');
        let input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'chart1';
        input.value = img;
        form.appendChild(input);
    }

    if (sections.includes('grafik_buku')) {
        let img = document.getElementById('chartStokBuku').toDataURL('image/png');
        let input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'chart2';
        input.value = img;
        form.appendChild(input);
    }

    if (sections.includes('grafik_user')) {
        let img = document.getElementById('chartUser').toDataURL('image/png');
        let input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'chart3';
        input.value = img;
        form.appendChild(input);
    }

    document.getElementById('exportForm').submit();
    $('#exportModal').modal('hide');
});
</script>

<script>
$('#exportModal').on('show.bs.modal', function () {

    // CHECKBOX
    document.querySelectorAll('.export-section').forEach(cb => {
        cb.checked = true;
    });

    // TAHUNAN
    document.getElementById('modeYear').checked = true;
    document.getElementById('modeRange').checked = false;

    // TANGGAL
    document.getElementById('year').value = "{{ date('Y') }}";
    document.getElementById('start_date').value = "{{ now()->startOfMonth()->format('Y-m-d') }}";
    document.getElementById('end_date').value = "{{ now()->endOfMonth()->format('Y-m-d') }}";

    // Toggle tampilan
    document.getElementById('yearInput').classList.remove('d-none');
    document.getElementById('rangeInput').classList.add('d-none');

    // HIDE ERROR
    const errorBox = document.getElementById('exportError');
    if (errorBox) errorBox.classList.add('d-none');
});
</script>

<script>
    // --- 1. CHART PEMINJAMAN PER BULAN (LINE) ---
    var ctxBulan = document.getElementById("chartPeminjamanBulan");
    var chartBulan = new Chart(ctxBulan, {
        type: 'line',
        data: {
            labels: ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"],
            datasets: [{
                label: "Jumlah Peminjaman",
                lineTension: 0.3,
                backgroundColor: "rgba(78, 115, 223, 0.05)",
                borderColor: "rgba(78, 115, 223, 1)",
                pointRadius: 3,
                pointBackgroundColor: "rgba(78, 115, 223, 1)",
                pointBorderColor: "rgba(78, 115, 223, 1)",
                pointHoverRadius: 3,
                pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: {!! json_encode($peminjamanPerBulan) !!}, // Data dari Controller
            }],
        },
        options: {
            maintainAspectRatio: false,
            layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
            scales: {
                xAxes: [{ gridLines: { display: false, drawBorder: false } }],
                yAxes: [{ ticks: { beginAtZero: true, maxTicksLimit: 5, padding: 10 }, gridLines: { color: "rgb(234, 236, 244)", zeroLineColor: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2], zeroLineBorderDash: [2] } }],
            },
            legend: { display: false },
        }
    });

    // --- 2. CHART SEMUA STOK BUKU (BAR) ---
    var ctxStok = document.getElementById("chartStokBuku");
    var chartStok = new Chart(ctxStok, {
        type: 'bar',
        data: {
            labels: {!! json_encode($labelStok) !!},
            datasets: [{
                label: "Total Stok",
                backgroundColor: "#4e73df",
                hoverBackgroundColor: "#2e59d9",
                borderColor: "#4e73df",
                data: {!! json_encode($dataStok) !!},
            }],
        },
        options: {
            maintainAspectRatio: false,
            layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
            scales: {
                xAxes: [{ gridLines: { display: false, drawBorder: false } }],
                yAxes: [{ ticks: { beginAtZero: true, maxTicksLimit: 5, padding: 10 }, gridLines: { color: "rgb(234, 236, 244)", zeroLineColor: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2], zeroLineBorderDash: [2] } }],
            },
            legend: { display: false },
        }
    });

    // --- 3. CHART TOTAL USER (DOUGHNUT) ---
    var ctxUser = document.getElementById("chartUser");
    var chartUser = new Chart(ctxUser, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($labelUser) !!},
            datasets: [{
                data: {!! json_encode($dataUser) !!},
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                caretPadding: 10,
            },
            legend: { position: 'bottom' },
            cutoutPercentage: 70,
        },
    });
</script>
@endsection