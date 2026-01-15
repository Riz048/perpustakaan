<nav class="bg-white shadow-md sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 md:px-8 py-4 flex items-center justify-between">
    <div class="flex items-center space-x-3">
        <img src="{{ asset('images/logo_sman1_hamparan_perak.png') }}" alt="SMAN 1 Hamparan Perak Logo" class="h-10 w-10 md:h-12 md:w-12 object-contain">
        <div class="font-semibold text-xs md:text-sm leading-tight">
            <div class="text-primary-blue">Perpustakaan</div>
            <div class="text-primary-blue">SMAN 1 Hamparan Perak</div>
        </div>
    </div>
    <ul class="hidden md:flex space-x-8 text-primary-blue font-medium">
        <li><a href="{{ route('beranda') }}#beranda" class="hover:text-blue-600">Beranda</a></li>
        <li><a href="{{ route('beranda') }}#tentang" class="hover:text-blue-600">Tentang</a></li>
        <li><a href="{{ route('beranda') }}#layanan" class="hover:text-blue-600">Layanan</a></li>
        <li><a href="{{ route('beranda') }}#kontak" class="hover:text-blue-600">Kontak</a></li>

        {{-- MENU DASHBOARD KHUSUS STAFF --}}
        @auth
            @if(in_array(auth()->user()->role, ['petugas','kep_perpus','kepsek','admin']))
                <li>
                    <a href="{{ route('dashboard') }}" class="hover:text-blue-600">
                        Dashboard
                    </a>
                </li>
            @endif
        @endauth
    </ul>

    <div class="hidden md:flex items-center space-x-3">
        @guest
            <a href="{{ route('login') }}"
                class="bg-primary-blue hover:bg-dark-blue text-white text-sm font-semibold py-2 px-5 rounded">
                Login
            </a>
        @endguest

        @auth
            <span class="text-primary-blue font-semibold text-sm">
                {{ Auth::user()->nama }} 👋
            </span>

            <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button 
                    type="submit"
                    class="ml-3 text-red-600 font-semibold hover:text-red-800">
                    ⏻
                </button>
            </form>
        @endauth
    </div>

    <button id="menuBtn" class="md:hidden text-primary-blue text-2xl">
        ☰
    </button>

    <div id="mobileMenu" class="hidden md:hidden absolute top-full left-0 w-full bg-white shadow-md px-6 py-4">
        <ul class="space-y-4 text-primary-blue font-medium">
            <li><a href="{{ route('beranda') }}#beranda"class="mobile-link">Beranda</a></li>
            <li><a href="{{ route('beranda') }}#tentang"class="mobile-link">Tentang</a></li>
            <li><a href="{{ route('beranda') }}#layanan"class="mobile-link">Layanan</a></li>
            <li><a href="{{ route('beranda') }}#kontak"class="mobile-link">Kontak</a></li>

            @auth
                @if(in_array(auth()->user()->role, ['petugas','kep_perpus','kepsek','admin']))
                    <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                @endif
            @endauth
        </ul>
    </div>
    </div>
</nav>

<script>
    const btn = document.getElementById('menuBtn');
    const menu = document.getElementById('mobileMenu');
    const links = document.querySelectorAll('#mobileMenu .mobile-link');

    btn.addEventListener('click', () => {
        menu.classList.toggle('hidden');
    });

    links.forEach(link => {
        link.addEventListener('click', () => {
            menu.classList.add('hidden');
        });
    });
</script>
