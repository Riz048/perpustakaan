@extends('layoutsuser.app')

@section('title', $judul)

@section('content')

<div class="bg-[#EBF0F5] min-h-screen pt-36 pb-20">

    <div class="max-w-7xl mx-auto px-6 lg:px-10">

        {{-- JUDUL --}}
        <h1 class="text-3xl md:text-5xl font-bold text-primary-blue mb-12">
            {{ $judul }}
        </h1>

        {{-- JIKA KOSONG --}}
        @if($buku->isEmpty())
            <p class="text-gray-500 text-xl">
                Belum ada buku untuk kategori ini.
            </p>
        @else

        {{-- GRID DAFTAR BUKU --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-8">

            @foreach($buku as $b)

            <a href="{{ route('detail.buku', $b->id) }}"
               class="block bg-white rounded-xl shadow hover:shadow-xl transition p-3">

                {{-- COVER --}}
                <div class="w-full h-48 rounded overflow-hidden shadow-sm bg-gray-100">
                    <img src="{{ bookImage($b->gambar) }}"
                         alt="{{ $b->judul }}"
                         class="w-full h-full object-cover">
                </div>

                {{-- JUDUL --}}
                <p class="mt-3 font-semibold text-primary-blue line-clamp-2">
                    {{ $b->judul }}
                </p>

                {{-- PENGARANG --}}
                <p class="text-sm text-gray-500 mt-1">
                    {{ $b->pengarang }}
                </p>

                {{-- KETERSEDIAAN BUKU --}}
                @if($b->kelas_akademik === 'non-akademik')
                    @if($b->buku_tersedia > 0)
                        <p class="text-sm text-green-600 mt-1">
                            Tersedia ({{ $b->buku_tersedia }})
                        </p>
                    @else
                        <p class="text-sm text-red-500 mt-1">
                            Tidak tersedia
                        </p>
                    @endif
                @endif
            </a>

            @endforeach

        </div>

        @endif

    </div>

</div>

@endsection
