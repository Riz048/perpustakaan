<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Perpustakaan Digital SMA Negeri 1 Hamparan Perak - @yield('title', 'Beranda')</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo_sman1_hamparan_perak.png') }}">
    
    {{-- Menggunakan CDN Tailwind (Ganti dengan @vite('resources/css/app.css') jika menggunakan Vite) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    
    {{-- Font Poppins & Inter --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            background-color: #ffffff;
        }
        /* Custom Styles dari kode asli */
        .gradient-bg { background: linear-gradient(to bottom, #DDE6F4, #FFFFFF, #F7F9FC); }
        .footer-bg { 
            background-color: #0A1D56; 
            /* Ganti URL ini dengan path gambar pattern Anda jika diperlukan */
            background-image: url('https://www.transparenttextures.com/patterns/asfalt-dark.png'); 
        }
        .text-primary-blue { color: #0A1D56; }
        .bg-primary-blue { background-color: #0A1D56; }
        .hover\:bg-dark-blue:hover { background-color: #112B65; }
    </style>
</head>
<body class="gradient-bg text-gray-900">

    @include('layoutsuser.navbar')

    <main>
        @yield('content')
    </main>

    @include('layoutsuser.footer')

</body>
</html>