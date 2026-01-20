@extends('layouts.admin')

@section('title', 'Dashboard Perpustakaan')

@section('styles')
<style>
    .stat-number {
        color: var(--primary);
        font-weight: 700;
        font-size: 1.25rem;
        line-height: 1.2;
    }

    .stat-label {
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .6px;
        color: #6c757d;
    }

    .card {
        border: 0;
        border-radius: .75rem;
        box-shadow: var(--card-shadow);
    }

    .card-body {
        padding: .85rem .85rem;
    }

    .card-header {
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .dashboard-chart-title {
        font-size: .9rem;
        font-weight: 700;
        color: var(--primary);
        letter-spacing: .3px;
        line-height: 1.2;
        margin: 0;
    }

    .chart-container {
        min-height: 180px;
        height: 100%;
        position: relative;
    }

    .chart-mode .btn {
        padding: .25rem .55rem;
        font-size: .7rem;
        line-height: 1.2;
    }

    .chart-mode {
        display: flex;
        align-items: center;
    }
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

            @include('admin.dashboard._export-modal')

        </div>
    </div>

    <div class="row justify-content-center">

        {{-- 4 CARD ATAS --}}
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="stat-label text-uppercase mb-1">Buku Akademik</div>
                    <div class="stat-number">{{ number_format($totalAkademik) }}</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="stat-label text-uppercase mb-1">Buku Bacaan</div>
                    <div class="stat-number">{{ number_format($totalNonAkademik) }}</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="stat-label text-uppercase mb-1">Total Siswa</div>
                    <div class="stat-number">{{ number_format($totalSiswa) }}</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="stat-label text-uppercase mb-1">Total Pegawai</div>
                    <div class="stat-number">{{ number_format($totalPegawai) }}</div>
                </div>
            </div>
        </div>

        <div class="w-100"></div>

        {{-- 2 CARD BAWAH (TENGAH) --}}
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="stat-label text-uppercase mb-1">Kunjungan Bulan Ini</div>
                    <div class="stat-number">{{ number_format($kunjunganBulanIni) }}</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="stat-label text-uppercase mb-1">Peminjaman Bulan Ini</div>
                    <div class="stat-number">{{ number_format($peminjamanBulanIni) }}</div>
                </div>
            </div>
        </div>

    </div>

    <div class="row">
        <!-- KUNJUNGAN -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <div class="chart-title">
                        Grafik Kunjungan {{ date('Y') }}
                    </div>
                    <div class="btn-group btn-group-sm chart-mode">
                        <button class="btn btn-outline-primary active" data-mode="bulan">Bulanan</button>
                        <button class="btn btn-outline-primary" data-mode="minggu">Mingguan</button>
                        <button class="btn btn-outline-primary" data-mode="hari">Harian</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartKunjunganBulan"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- PEMINJAMAN -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header py-2">
                    <div class="chart-title">
                        Grafik Peminjaman {{ date('Y') }}
                    </div>
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
        <!-- STOK BUKU -->
        <div class="col-xl-8 col-lg-7 mb-4">
            <div class="card h-100">
                <div class="card-header py-2">
                    <div class="chart-title">
                        Statistik Stok Buku (Eksemplar)
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartStokBuku"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- KOMPOSISI USER -->
        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card h-100">
                <div class="card-header py-2">
                    <div class="chart-title">
                        Komposisi User
                    </div>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="chart-container" style="max-width: 260px;">
                        <canvas id="chartUser"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('script')
<script src="{{ asset('vendor/chart.js/Chart.min.js') }}"></script>

<script>
// UTIL: BASE OPTION AGAR SEMUA LINE CHART KONSISTEN
function baseLineOptions() {
    return {
        maintainAspectRatio: false,
        layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
        scales: {
            xAxes: [{ gridLines: { display: false, drawBorder: false } }],
            yAxes: [{
                ticks: { beginAtZero: true, maxTicksLimit: 5, padding: 10 },
                gridLines: {
                    color: "rgb(234,236,244)",
                    zeroLineColor: "rgb(234,236,244)",
                    drawBorder: false,
                    borderDash: [2],
                    zeroLineBorderDash: [2]
                }
            }]
        },
        legend: { display: false }
    };
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // Export Modal
    const modeYear   = document.getElementById('modeYear');
    const modeRange  = document.getElementById('modeRange');
    const yearInput  = document.getElementById('yearInput');
    const rangeInput = document.getElementById('rangeInput');

    function toggleMode() {
        yearInput.classList.toggle('d-none', !modeYear.checked);
        rangeInput.classList.toggle('d-none', modeYear.checked);
    }

    if (modeYear && modeRange) {
        modeYear.addEventListener('change', toggleMode);
        modeRange.addEventListener('change', toggleMode);
        toggleMode();
    }

    const btnExport = document.getElementById('confirmExport');
    if (btnExport) {
        btnExport.addEventListener('click', function () {
            const errorBox = document.getElementById('exportError');
            if (errorBox) errorBox.classList.add('d-none');

            const sections = [...document.querySelectorAll('.export-section:checked')]
                .map(el => el.value);

            if (!sections.length) {
                if (errorBox) errorBox.classList.remove('d-none');
                return;
            }

            document.querySelector('[name="sections"]').value = JSON.stringify(sections);

            document.querySelector('[name=mode]').value =
                modeYear.checked ? 'year' : 'range';

            if (modeYear.checked) {
                document.querySelector('[name=year]').value =
                    document.getElementById('year').value;
            } else {
                document.querySelector('[name=start_date]').value =
                    document.getElementById('start_date').value;
                document.querySelector('[name=end_date]').value =
                    document.getElementById('end_date').value;
            }

            document.getElementById('exportForm').submit();
            $('#exportModal').modal('hide');
        });
    }

    // Chart Peminjaman Per Bulan
    new Chart(document.getElementById("chartPeminjamanBulan"), {
        type: 'line',
        data: {
            labels: ["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des"],
            datasets: [{
                label: "Jumlah Peminjaman",
                data: {!! json_encode($peminjamanPerBulan) !!},
                borderColor: "rgba(78,115,223,1)",
                backgroundColor: "rgba(78,115,223,.05)",
                pointRadius: 3,
                pointBorderWidth: 2,
                lineTension: .3
            }]
        },
        options: baseLineOptions()
    });

    // Chart Stok Buku
    new Chart(document.getElementById("chartStokBuku"), {
        type: 'bar',
        data: {
            labels: {!! json_encode($labelStok) !!},
            datasets: [{
                label: "Total Stok",
                data: {!! json_encode($dataStok) !!},
                backgroundColor: "#4e73df",
                borderColor: "#4e73df"
            }]
        },
        options: baseLineOptions()
    });

    // Chart Distribusi User
    new Chart(document.getElementById("chartUser"), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($labelUser) !!},
            datasets: [{
                data: {!! json_encode($dataUser) !!},
                backgroundColor: ['#4e73df','#1cc88a','#36b9cc','#f6c23e','#e74a3b'],
                hoverBackgroundColor: ['#2e59d9','#17a673','#2c9faf','#dda20a','#be2617'],
                hoverBorderColor: "rgba(234,236,244,1)"
            }]
        },
        options: {
            maintainAspectRatio: false,
            legend: { position: 'bottom' },
            cutoutPercentage: 70
        }
    });

    // Chart Kunjungan (Hari / Minggu / Bulan)
    const kunjunganData = {
        bulan: {
            labels: ["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des"],
            data: {!! json_encode($kunjunganBulanan) !!}
        },
        minggu: {
            labels: {!! json_encode($labelMingguan) !!},
            data: {!! json_encode($kunjunganMingguan) !!},
            range: {!! json_encode($rangeMingguan) !!}
        },
        hari: {
            labels: {!! json_encode($labelHarian) !!},
            data: {!! json_encode($kunjunganHarian) !!}
        }
    };

    const chartKunjungan = new Chart(
        document.getElementById("chartKunjunganBulan"),
        {
            type: 'line',
            data: {
                labels: kunjunganData.bulan.labels,
                datasets: [{
                    label: "Jumlah Kunjungan",
                    data: kunjunganData.bulan.data,
                    borderColor: "rgba(78,115,223,1)",
                    backgroundColor: "rgba(78,115,223,.05)",
                    pointRadius: 3,
                    pointBorderWidth: 2,
                    lineTension: .3
                }]
            },
            options: {
                ...baseLineOptions(),
                tooltips: {
                    callbacks: {
                        title: function (tooltipItems) {
                            const index = tooltipItems[0].index;
                            const mode = document.querySelector('[data-mode].active').dataset.mode;

                            if (mode === 'minggu') {
                                return kunjunganData.minggu.range[index];
                            }

                            return tooltipItems[0].label;
                        }
                    }
                }
            }
        }
    );

    // Kontrol Zoom (hari / minggu / bulan)
    document.querySelectorAll('[data-mode]').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-mode]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const mode = this.dataset.mode;
            chartKunjungan.data.labels = kunjunganData[mode].labels;
            chartKunjungan.data.datasets[0].data = kunjunganData[mode].data;
            chartKunjungan.update();
        });
    });

});
</script>
@endsection
