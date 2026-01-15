@extends('layoutsuser.app')

@section('title', 'Riwayat Transaksi')

@section('content')

<style>
.header-bg-riwayat {
    background: linear-gradient(180deg, rgba(235, 240, 245, 1) 0%, rgba(235, 240, 245, 0) 100%);
}

.text-primary-blue {
    color: #0A1D56;
}

.bg-primary-blue {
    background-color: #0A1D56;
}
</style>

<main class="flex-grow relative">

    {{-- HEADER BACKGROUND --}}
    <div class="header-bg-riwayat absolute top-0 left-0 w-full h-[160px] md:h-[200px] z-0"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">

        {{-- ================= DESKTOP ================= --}}
        <div class="hidden md:block pt-36 pb-28">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-24">
                <h1 class="text-2xl sm:text-4xl font-extrabold text-primary-blue leading-tight">
                    Riwayat <span class="bg-primary-blue text-white px-4 py-2">Transaksi</span> Anda
                </h1>
                <div class="mt-4 md:mt-0 relative w-full md:w-80">
                    <input id="searchInput" type="text" placeholder="Cari..."
                        class="w-full py-2 pl-4 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-blue">
                    <button class="absolute right-0 top-0 mt-3 mr-3 text-gray-500 hover:text-primary-blue">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Daftar Transaksi Dinamis --}}
            <div id="transaction-list" class="space-y-8">
                @forelse($riwayat as $index => $pinjam)
                @php
                // Detail buku
                $first = $pinjam->detail->first();
                $judul = $first?->buku?->judul ?? 'Judul Buku Tidak Diketahui';
                $gambar = $first?->buku?->gambar ?? null;

                // Pengembalian
                $pengembalian = $pinjam->pengembalian;
                $hasReturn = $pengembalian !== null;

                // Deadline 7 hari
                $tanggalPinjam = \Carbon\Carbon::parse($pinjam->tanggal_pinjam)->startOfDay();
                $deadline = $tanggalPinjam->copy()->addDays($pinjam->lama_pinjam);
                $today = now()->startOfDay();

                // Sudah dikembalikan
                if ($hasReturn) {
                $labelTanggal = 'Tanggal Pengembalian';
                $tanggalKembaliText = \Carbon\Carbon::parse($pengembalian->tanggal_kembali)->format('d M Y');

                $iconColor = 'text-green-600';
                $dateColor = 'text-green-600';
                $statusColor = 'bg-green-600';
                $statusText = 'SUDAH DIKEMBALIKAN';
                }

                // Masih dipinjam
                elseif ($today->lte($deadline)) {
                $labelTanggal = 'Batas Pengembalian';
                $tanggalKembaliText = $deadline->format('d M Y');

                $iconColor = 'text-yellow-500';
                $dateColor = 'text-yellow-600';
                $statusColor = 'bg-yellow-500';
                $statusText = 'DIPINJAM';
                }

                // Terlambat
                else {
                $daysLate = $deadline->diffInDays($today);

                $labelTanggal = 'Terlambat ' . $daysLate . ' Hari';
                $tanggalKembaliText = $deadline->format('d M Y');

                $iconColor = 'text-red-600';
                $dateColor = 'text-red-600';
                $statusColor = 'bg-red-600';
                $statusText = 'TERLAMBAT';
                }
                @endphp

                <div class="transaction-item bg-white p-4 sm:p-6 rounded-xl shadow-md flex flex-col md:flex-row items-start md:items-center space-y-4 md:space-y-0 md:space-x-6"
                    data-judul="{{ strtolower($judul) }}" data-status="{{ strtolower($statusText) }}">

                    <span
                        class="text-4xl font-extrabold text-gray-400 absolute md:static -top-4 -left-4 md:hidden">{{ $index + 1 }}</span>
                    <span class="text-5xl font-extrabold text-gray-400 hidden md:block">{{ $index + 1 }}</span>

                    <div class="flex items-center space-x-3 w-full md:w-2/5">
                        <div class="w-28 h-32 rounded-md overflow-hidden shadow-sm bg-gray-100 flex-shrink-0">
                            @if($gambar)
                            <img src="{{ bookImage($gambar) }}" alt="{{ $judul }}" class="w-full h-full object-cover">
                            @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400 text-xs">
                                No Image
                            </div>
                            @endif
                        </div>

                        <p class="font-semibold text-lg text-primary-blue">
                            {{ $judul }}
                        </p>
                    </div>

                    <div
                        class="flex flex-col sm:flex-row md:flex-grow space-y-3 sm:space-y-0 sm:space-x-6 w-full md:w-3/5">
                        <div class="flex flex-col space-y-3 w-full sm:w-auto sm:flex-grow sm:space-x-0 ml-24">
                            <div class="flex items-center space-x-2 bg-gray-100 p-2 rounded-lg text-gray-700 w-full">
                                <svg class="w-5 h-5 text-primary-blue" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <div class="flex flex-col">
                                    <span class="text-xs text-gray-500">Tanggal Peminjaman</span>
                                    <span
                                        class="font-medium">{{ \Carbon\Carbon::parse($pinjam->tanggal_pinjam)->format('d M Y') }}</span>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2 bg-gray-100 p-2 rounded-lg text-gray-700 w-full">
                                <svg class="w-5 h-5 {{ $iconColor }}" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <div class="flex flex-col">
                                    <span class="text-xs text-gray-500">{{ $labelTanggal }}</span>
                                    <span class="font-medium {{ $dateColor }}">
                                        {{ $tanggalKembaliText }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-center sm:justify-end md:justify-center w-full sm:w-40">
                            <span
                                class="{{ $statusColor }} text-white text-sm font-semibold py-1.5 px-3 rounded-md w-full text-center">
                                {{ $statusText }}
                            </span>
                        </div>
                    </div>

                </div>
                @empty
                <div class="text-center py-10 bg-white rounded-xl shadow-md">
                    <p class="text-gray-500 text-xl">Anda belum memiliki riwayat transaksi.</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- ================= MOBILE / PHONE ================= --}}
        <div class="block md:hidden pt-28 pb-32">

            {{-- TITLE + SEARCH --}}
            <div class="flex flex-col mb-10">
                <h1 class="text-lg sm:text-4xl font-extrabold text-primary-blue leading-tight">
                    Riwayat <span class="bg-primary-blue text-white px-2 py-1 sm:px-4 sm:py-2">Transaksi</span> Anda
                </h1>

                <div class="mt-8 relative w-full">
                    <input id="searchInput" type="text" placeholder="Cari..."
                        class="w-full py-3 pl-3 pr-9 text-sm sm:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-blue">
                    <button class="absolute right-0 top-0 mt-2.5 mr-3 text-gray-500 hover:text-primary-blue">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Daftar Transaksi --}}
            <div id="transaction-list" class="space-y-6">
                @forelse($riwayat as $index => $pinjam)
                @php
                // Detail buku
                $first = $pinjam->detail->first();
                $judul = $first?->buku?->judul ?? 'Judul Buku Tidak Diketahui';
                $gambar = $first?->buku?->gambar ?? null;

                // Pengembalian
                $pengembalian = $pinjam->pengembalian;
                $hasReturn = $pengembalian !== null;

                // Deadline 7 hari
                $tanggalPinjam = \Carbon\Carbon::parse($pinjam->tanggal_pinjam)->startOfDay();
                $deadline = $tanggalPinjam->copy()->addDays($pinjam->lama_pinjam);
                $today = now()->startOfDay();

                // Sudah dikembalika
                if ($hasReturn) {
                $labelTanggal = 'Tanggal Pengembalian';
                $tanggalKembaliText = \Carbon\Carbon::parse($pengembalian->tanggal_kembali)->format('d M Y');

                $iconColor = 'text-green-600';
                $dateColor = 'text-green-600';
                $statusColor = 'bg-green-600';
                $statusText = 'SUDAH DIKEMBALIKAN';
                }

                // Masih dipinjam
                elseif ($today->lte($deadline)) {
                $labelTanggal = 'Batas Pengembalian';
                $tanggalKembaliText = $deadline->format('d M Y');

                $iconColor = 'text-yellow-500';
                $dateColor = 'text-yellow-600';
                $statusColor = 'bg-yellow-500';
                $statusText = 'DIPINJAM';
                }

                // Terlambat
                else {
                $daysLate = $deadline->diffInDays($today);

                $labelTanggal = 'Terlambat ' . $daysLate . ' Hari';
                $tanggalKembaliText = $deadline->format('d M Y');

                $iconColor = 'text-red-600';
                $dateColor = 'text-red-600';
                $statusColor = 'bg-red-600';
                $statusText = 'TERLAMBAT';
                }
                @endphp

                <div class="transaction-item bg-white p-4 rounded-xl shadow-md flex flex-col space-y-4">
                    data-judul="{{ strtolower($judul) }}" data-status="{{ strtolower($statusText) }}">

                    {{-- NOMOR + GAMBAR + JUDUL --}}
                    <div class="flex items-center space-x-3 w-full sm:w-2/5">
                        <div class="flex-shrink-0 text-2xl font-bold text-gray-400 flex items-center justify-center">
                            {{ $index + 1 }}
                        </div>

                        <div
                            class="w-16 h-20 sm:w-28 sm:h-32 rounded-md overflow-hidden shadow-sm bg-gray-100 flex-shrink-0">
                            @if($gambar)
                            <img src="{{ bookImage($gambar) }}" alt="{{ $judul }}" class="w-full h-full object-cover">
                            @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400 text-xs">
                                No Image
                            </div>
                            @endif
                        </div>

                        <p class="font-semibold text-sm text-primary-blue line-clamp-2">
                            {{ $judul }}
                        </p>
                    </div>

                    {{-- TANGGAL + STATUS --}}
                    <div
                        class="flex flex-col sm:flex-row sm:flex-grow space-y-3 sm:space-y-0 sm:space-x-6 w-full sm:w-3/5">
                        <div class="flex flex-col space-y-3 w-full sm:flex-grow">

                            <div class="flex items-center space-x-2 bg-gray-100 p-2 sm:p-3 rounded-lg text-gray-700">
                                <svg class="w-5 h-5 text-primary-blue" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <div class="flex flex-col">
                                    <span class="text-[10px] sm:text-xs text-gray-500">Tanggal Peminjaman</span>
                                    <span class="font-medium text-sm sm:text-base">
                                        {{ \Carbon\Carbon::parse($pinjam->tanggal_pinjam)->format('d M Y') }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2 bg-gray-100 p-2 rounded-lg text-gray-700 w-full">
                                <svg class="w-5 h-5 {{ $iconColor }}" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <div class="flex flex-col">
                                    <span class="text-xs text-gray-500">{{ $labelTanggal }}</span>
                                    <span class="font-medium {{ $dateColor }}">
                                        {{ $tanggalKembaliText }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-center sm:justify-end w-full sm:w-40">
                            <span
                                class="{{ $statusColor }} text-white font-bold py-2 px-4 rounded-lg w-full text-center">
                                {{ $statusText }}
                            </span>
                        </div>

                    </div>
                </div>
                @empty
                <div class="text-center py-10 bg-white rounded-xl shadow-md">
                    <p class="text-gray-500 text-xl">Anda belum memiliki riwayat transaksi.</p>
                </div>
                @endforelse
            </div>

        </div>

    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('searchInput');
    const items = document.querySelectorAll('.transaction-item');

    input.addEventListener('input', function() {
        const keyword = this.value.trim().toLowerCase();

        items.forEach(item => {
            if (keyword === '') {
                item.style.display = '';
                return;
            }

            const judul = item.getAttribute('data-judul') || '';
            const status = item.getAttribute('data-status') || '';

            item.style.display =
                judul.includes(keyword) || status.includes(keyword) ?
                '' :
                'none';
        });
    });
});
</script>

@endsection