@extends('layoutsuser.app')

@section('title', $buku->judul)

@section('content')

<div class="bg-[#dbe4f1] min-h-screen pt-36 pb-20 font-sans">
    
    <div class="max-w-6xl mx-auto px-6 lg:px-10">

        <h1 class="text-5xl font-bold text-gray-800 tracking-tight mb-16">
            {{ $buku->judul }}
        </h1>
        
        <div class="flex flex-col md:flex-row gap-10 items-start">

            {{-- COVER --}}
            <div class="w-full md:w-[250px] flex-shrink-0">
                <img src="{{ bookImage($buku->gambar) }}"
                     alt="Cover {{ $buku->judul }}"
                     class="w-full h-[300px] object-cover rounded shadow-2xl">
            </div>

            {{-- INFO --}}
            <div class="flex-1 pt-0 md:pt-4">

                <div class="bg-gray-800 text-white w-40 p-3 mb-6 text-center">
                    <h2 class="text-2xl font-medium">
                        Sinopsis
                    </h2>
                </div>

                <p class="text-lg mt-3 text-gray-700">
                    <strong>Pengarang:</strong> {{ $buku->pengarang }}
                </p>

                <p class="text-lg mt-1 text-gray-700">
                    <strong>Penerbit:</strong> {{ $buku->nama_penerbit }}
                </p>

                <p class="text-lg mt-1 text-gray-700">
                    <strong>ISBN:</strong> {{ $buku->isbn }}
                </p>

                <p class="text-lg mt-1 mb-6 text-gray-700">
                    <strong>Tahun Terbit:</strong> {{ $buku->tahun_terbit }}
                </p>

                <p class="text-base text-gray-800 leading-relaxed text-justify">
                    {{ $buku->sinopsis }}
                </p>

            </div>

        </div>

    </div>

</div>

@endsection
