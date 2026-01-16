<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
    body {
        font-family: DejaVu Sans;
        font-size: 11px
    }

    h1 {
        font-size: 18px
    }

    h2 {
        border-bottom: 1px solid #ccc;
        margin-top: 18px
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 6px
    }

    th, td {
        border: 1px solid #ddd;
        padding: 4px
    }

    th {
        background: #f4f4f4
    }

    .section {
        margin-bottom: 16px
    }

    .chart {
        display: flex;
        justify-content: center;
        margin: 8px 0;
    }

    .chart img {
        width: 45%
    }

    .empty {
        text-align: center;
        font-style: italic;
        color: #888
    }

    .no-col {
        width: 30px;
        text-align: center;
    }
    </style>
</head>

<body>

    <h1>Laporan Statistik Perpustakaan</h1>
    Tahun: {{ $currentYear }} <br>
    Tanggal Cetak: {{ now()->format('d-m-Y') }} <br>
    Kepala Perpustakaan: {{ $kepalaPerpus->nama ?? '-' }}

    {{-- RINGKASAN --}}
    @if($sections->contains('ringkasan'))
        <h2>Ringkasan Umum</h2>
        <table>
            <tr>
                <td>Total Judul Buku Akademik</td>
                <td>{{ $totalAkademik }}</td>
            </tr>
            <tr>
                <td>Total Judul Buku Non-Akademik</td>
                <td>{{ $totalNonAkademik }}</td>
            </tr>
            <tr>
                <td>Total Siswa Terdaftar</td>
                <td>{{ $totalSiswa }}</td>
            </tr>
            <tr>
                <td>Total Guru</td>
                <td>{{ $totalGuru }}</td>
            </tr>
            <tr>
                <td>Total Petugas</td>
                <td>{{ $totalPetugas }}</td>
            </tr>
        </table>
    @endif

    {{-- KONDISI BUKU --}}
    @if($sections->contains('buku'))
        <h2>Kondisi Buku</h2>
        <table>
            <tr>
                <th>Kondisi</th>
                <th>Jumlah Buku</th>
            </tr>
            <tr>
                <td>Baik</td>
                <td>{{ $stokBaik }}</td>
            </tr>
            <tr>
                <td>Rusak</td>
                <td>{{ $stokRusak }}</td>
            </tr>
            <tr>
                <td>Hilang</td>
                <td>{{ $stokHilang }}</td>
            </tr>
        </table>

        {{-- SEBARAN BUKU --}}
        <h2>Buku per Kategori</h2>
        <table>
            <tr>
                <th>Kategori</th>
                <th>Jumlah Judul</th>
                <th>Jumlah Eksemplar</th>
            </tr>
            <tr>
                <td>Kelas 10</td>
                <td>{{ $judulKelas10 }}</td>
                <td>{{ $eksKelas10 }}</td>
            </tr>
            <tr>
                <td>Kelas 11</td>
                <td>{{ $judulKelas11 }}</td>
                <td>{{ $eksKelas11 }}</td>
            </tr>
            <tr>
                <td>Kelas 12</td>
                <td>{{ $judulKelas12 }}</td>
                <td>{{ $eksKelas12 }}</td>
            </tr>
            <tr>
                <td>Buku Fiksi</td>
                <td>{{ $judulFiksi }}</td>
                <td>{{ $eksFiksi }}</td>
            </tr>
            <tr>
                <td>Buku Umum (Non-Fiksi)</td>
                <td>{{ $judulUmum }}</td>
                <td>{{ $eksUmum }}</td>
            </tr>
        </table>
    @endif

    {{-- GRAFIK BUKU --}}
    @if($sections->contains('grafik'))
        <h3>Grafik Buku</h3>
        <div class="chart">
            <img src="{{ $chart2 }}">
        </div>
    @endif

    {{-- DAFTAR BUKU --}}
    @if($sections->contains('buku'))
        <h2>Daftar Buku Berdasarkan Status</h2>

        @foreach(['Baik'=>$listBaik,'Rusak'=>$listRusak,'Hilang'=>$listHilang] as $label=>$list)
            <h3>Buku Kondisi {{ $label }}</h3>

            @if($list->isEmpty())
                <div class="empty">Tidak ada data</div>
            @else
                <table>
                    <tr>
                        <th class="no-col">No</th>
                        <th>Kategori</th>
                        <th>Judul</th>
                        <th>Pengarang</th>
                        <th>Jumlah</th>
                    </tr>

                    @foreach($list as $i => $b)
                        <tr>
                            <td class="no-col">{{ $i + 1 }}</td>
                            <td>{{ $b->kategori }}</td>
                            <td>{{ $b->judul }}</td>
                            <td>{{ $b->pengarang }}</td>
                            <td>{{ $b->jumlah }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        @endforeach

    @endif

    {{-- PEMINJAMAN --}}
    @if($sections->contains('peminjaman'))
        <h2>Peminjaman per Bulan</h2>
        <table>
            <tr>
                <th>Bulan</th>
                <th>Jumlah</th>
            </tr>
            @php
                $bulan=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            @endphp

            @foreach($peminjaman as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td>{{ $row['total'] }}</td>
                </tr>
            @endforeach

        </table>
    @endif

    @if($sections->contains('buku_dipinjam'))
        <h2>Daftar Buku yang Dipinjam</h2>

        @if($listDipinjam->isEmpty())
            <div class="empty">Tidak ada data</div>
        @else
            <table>
                <tr>
                    <th>No</th>
                    <th>Kategori</th>
                    <th>Judul / Paket</th>
                    <th>Tanggal Pinjam</th>
                    <th>Status</th>
                    <th>Jumlah</th>
                </tr>
                @foreach($listDipinjam as $i => $b)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $b->kategori }}</td>
                        <td>{{ $b->judul }}</td>
                        <td>{{ \Carbon\Carbon::parse($b->tanggal_pinjam)->format('d-m-Y') }}</td>
                        <td>{{ $b->status }}</td>
                        <td>{{ $b->jumlah }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    @endif

    {{-- DISTRIBUSI USER --}}
    @if($sections->contains('list_user'))
        <h2>Distribusi Pengguna</h2>

        @if($userDistribusi->isEmpty())
            <div class="empty">Tidak ada data</div>
        @else
            @php
                $urutanRole = [
                'siswa' => 'Siswa',
                'guru' => 'Guru',
                'petugas' => 'Petugas',
                'kep_perpus' => 'Kepala Perpustakaan',
                'kepsek' => 'Kepala Sekolah',
                ];
            @endphp

        <table>
            <tr>
                <th>Role</th>
                <th>Jumlah User</th>
            </tr>

            @foreach($urutanRole as $key => $label)
                @php
                    $row = $userDistribusi->firstWhere('role', $key);
                @endphp
                <tr>
                    <td>{{ $label }}</td>
                    <td>{{ $row->total ?? 0 }}</td>
                </tr>
            @endforeach
        </table>

        @endif
    @endif

    {{-- GRAFIK USER --}}
    @if($sections->contains('grafik'))
        <h3>Grafik Komposisi User</h3>
        <div class="chart">
            <img src="{{ $chart3 }}">
        </div>
    @endif

    @if($sections->contains('list_user'))

    {{-- DAFTAR PENGGUNA --}}
    <h2>Daftar Pengguna</h2>

    <h3>Siswa</h3>

    @if($listSiswa->isEmpty())
        <div class="empty">Tidak ada data</div>
    @else
        <table>
            <tr>
                <th class="no-col">No</th>
                <th>Nama</th>
                <th>Kelas</th>
            </tr>
            @foreach($listSiswa as $i => $u)
                <tr>
                    <td class="no-col">{{ $i + 1 }}</td>
                    <td>{{ $u->nama }}</td>
                    <td>
                        @if($u->tingkat)
                            {{ $u->tingkat }}-{{ $u->rombel }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    <h3>Guru</h3>

    @if($listGuru->isEmpty())
        <div class="empty">Tidak ada data</div>
    @else
        <table>
            <tr>
                <th class="no-col">No</th>
                <th>Nama</th>
            </tr>
            @foreach($listGuru as $i => $u)
                <tr>
                    <td class="no-col">{{ $i + 1 }}</td>
                    <td>{{ $u->nama }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h3>Petugas</h3>

    @if($listPetugas->isEmpty())
        <div class="empty">Tidak ada data</div>
    @else
        <table>
            <tr>
                <th class="no-col">No</th>
                <th>Nama</th>
            </tr>
            @foreach($listPetugas as $i => $u)
                <tr>
                    <td class="no-col">{{ $i + 1 }}</td>
                    <td>{{ $u->nama }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @endif

    <!-- <br><br><br>

    <table style="width:100%; border:none;">
        <tr>
            <td style="width:60%; border:none;"></td>
            <td style="text-align:center; border:none;">
                Hamparan Perak, {{ now()->translatedFormat('d F Y') }} <br>
                Kepala Perpustakaan <br><br><br><br>
                <strong>{{ $kepalaPerpus->nama ?? '____________________' }}</strong>
            </td>
        </tr>
    </table> -->
</body>

</html>