@extends('layoutsuser.app') 

@section('title', 'Beranda')

@section('content')

{{-- ========================================= --}}
{{--               HERO SECTION               --}}
{{-- ========================================= --}}
<div class="header-bg-riwayat bg-[#EBF0F5] min-h-[70vh] md:h-screen flex flex-col justify-center items-center py-16 md:py-20 px-4 md:px-20 text-center">
    <h1 class="text-primary-blue text-2xl md:text-5xl font-bold mb-8">
        Temukan Buku Favoritmu
    </h1>
    
    <div class="max-w-md mx-auto relative w-full px-2">
        <form method="GET" action="{{ route('user.referensi.home') }}">
            <input 
                type="text" 
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari judul atau pengarang..." 
                class="w-full py-3 pl-4 pr-12 border border-blue-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-blue text-sm md:text-base"
            >
            
            <button type="submit" 
                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-primary-blue">
                🔍
            </button>
        </form>
    </div>
</div>


{{-- ========================================= --}}
{{--              HASIL PENCARIAN             --}}
{{-- ========================================= --}}
@if(!is_null($bukuHasil))
<section class="px-6 md:px-20 py-12">

    <h3 class="text-primary-blue text-3xl font-bold mb-8">
        Hasil Pencarian: "{{ $keyword }}"
    </h3>

    @if($bukuHasil->isEmpty())
        <p class="text-gray-600">Tidak ada buku ditemukan.</p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">

            @foreach ($bukuHasil as $b)
            <a href="{{ route('detail.buku', $b->id) }}" 
               class="block bg-white shadow rounded-lg overflow-hidden">

                <img src="{{ bookImage($b->gambar) }}"
                     class="w-full h-40 md:h-48 object-cover">

                <div class="p-3">
                    <h4 class="font-semibold text-primary-blue line-clamp-2">{{ $b->judul }}</h4>
                    <p class="text-sm text-gray-600">{{ $b->pengarang }}</p>
                </div>
            </a>
            @endforeach

        </div>
    @endif
</section>

{{-- ========================================= --}}
{{--      KATEGORI + KOLEKSI TERBARU (ELSE)    --}}
{{-- ========================================= --}}
@else

{{-- MENU KATEGORI --}}
<section class="bg-[#EBF0F5] py-12 px-4 md:p-10 text-center">
    <h3 class="text-white bg-primary-blue inline-block px-6 py-3 text-3xl font-bold mb-10 md:mb-16">
        Pilih Buku Berdasarkan Kategori
    </h3>

    @php
        $kelasSiswa = null;

        if (Auth::check() && Auth::user()->role === 'siswa') {
            $kelasSiswa = optional(Auth::user()->kelasAktif)->tingkat;
        }
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">

        {{-- KELAS 10 --}}
        @if(!$kelasSiswa || $kelasSiswa == 10)
        <a href="{{ route('user.referensi.kategori', 'kelas10') }}"
        class="block p-5 md:p-6 bg-white shadow-lg rounded-xl hover:shadow-xl transition">
            <h4 class="text-xl font-semibold text-primary-blue">Kelas 10</h4>
        </a>
        @endif

        {{-- KELAS 11 --}}
        @if(!$kelasSiswa || $kelasSiswa == 11)
        <a href="{{ route('user.referensi.kategori', 'kelas11') }}"
        class="block p-5 md:p-6 bg-white shadow-lg rounded-xl hover:shadow-xl transition">
            <h4 class="text-xl font-semibold text-primary-blue">Kelas 11</h4>
        </a>
        @endif

        {{-- KELAS 12 --}}
        @if(!$kelasSiswa || $kelasSiswa == 12)
        <a href="{{ route('user.referensi.kategori', 'kelas12') }}"
        class="block p-5 md:p-6 bg-white shadow-lg rounded-xl hover:shadow-xl transition">
            <h4 class="text-xl font-semibold text-primary-blue">Kelas 12</h4>
        </a>
        @endif

        {{-- BUKU BACAAN --}}
        <a href="{{ route('user.referensi.kategori', 'bacaan') }}"
        class="block p-5 md:p-6 bg-white shadow-lg rounded-xl hover:shadow-xl transition">
            <h4 class="text-xl font-semibold text-primary-blue">Buku Bacaan</h4>
        </a>

    </div>
</section>


{{-- ========================================= --}}
{{--              KOLEKSI TERBARU             --}}
{{-- ========================================= --}}
<section class="bg-gradient-to-t from-[#EBF0F5] to-[#EBF0F5]/0 w-full min-h-[60vh] md:h-[80vh] flex flex-col justify-center items-center px-4 md:px-20">

    <h3 class="text-primary-blue font-semibold text-xl md:text-3xl md:text-4xl mb-12">
        Koleksi Terbaru
    </h3>

    <div class="relative flex items-center w-full max-w-6xl">

        {{-- TOMBOL KIRI --}}
        <button aria-label="Previous" 
                class="btn-prev hidden md:flex bg-white rounded-full p-3 shadow-md hover:bg-gray-100 z-10">
            <svg xmlns="http://www.w3.org/2000/svg" 
                 class="h-8 w-8 text-primary-blue"
                 fill="none" viewBox="0 0 24 24" 
                 stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>

        {{-- SLIDER --}}
        <div class="slider-buku flex overflow-x-auto space-x-6 mx-6 w-full hide-scrollbar">

            @foreach ($bukuTerbaru as $b)
            <a href="{{ route('detail.buku', $b->id) }}" 
            class="card block w-40 md:w-52 flex-shrink-0 rounded overflow-hidden shadow-lg bg-white relative">

                <div class="relative w-full h-56 md:h-64">

                    <img src="{{ bookImage($b->gambar) }}"
                        alt="{{ $b->judul }}"
                        class="w-full h-full object-cover">

                    <!-- Perbaikan gradient -->
                    <div class="absolute bottom-0 left-0 w-full h-28 title-gradient"></div>

                    <!-- Judul kini jauh lebih keliatan -->
                    <div class="book-title absolute bottom-3 left-3 right-3 text-white
                                text-sm font-semibold z-10 line-clamp-2">
                        {{ $b->judul }}
                    </div>
                </div>

            </a>
            @endforeach

        </div>

        {{-- TOMBOL KANAN --}}
        <button aria-label="Next" 
                class="btn-next hidden md:flex bg-white rounded-full p-3 shadow-md hover:bg-gray-100 z-10">
            <svg xmlns="http://www.w3.org/2000/svg" 
                 class="h-8 w-8 text-primary-blue"
                 fill="none" viewBox="0 0 24 24" 
                 stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>

    </div>
</section>

@endif {{-- END ELSE SEARCH --}}


{{-- ========================================= --}}
{{--                 SCRIPT JS                --}}
{{-- ========================================= --}}
<script>
document.addEventListener("DOMContentLoaded", function() {
    const slider = document.querySelector(".slider-buku");
    const prevBtn = document.querySelector(".btn-prev");
    const nextBtn = document.querySelector(".btn-next");

    const scrollAmount = window.innerWidth < 768 ? 220 : 300;
    nextBtn.addEventListener("click", () => slider.scrollBy({ left: scrollAmount, behavior: 'smooth' }));
    prevBtn.addEventListener("click", () => slider.scrollBy({ left: -scrollAmount, behavior: 'smooth' }));
});
</script>

<style>
.hide-scrollbar::-webkit-scrollbar { display: none; }

/* Gradient dasar */
.card .title-gradient {
    background: linear-gradient(to top, rgba(0,0,0,0.65), transparent);
    transition: background .25s ease;
}

/* Hover: sedikit lebih gelap, biar judul makin jelas */
.card:hover .title-gradient {
    background: linear-gradient(to top, rgba(0,0,0,0.80), transparent);
}
</style>

@endsection
