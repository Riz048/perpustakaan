<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">

    {{-- BRAND --}}
    <a class="sidebar-brand d-flex align-items-center justify-content-center">
        <div class="sidebar-brand-icon rotate-n-15"><i class="fas fa-book"></i></div>
        <div class="sidebar-brand-text mx-2">Perpustakaan</div>
    </a>

    <hr class="sidebar-divider my-0">

    {{-- DASHBOARD --}}
    <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <a class="nav-link sidebar-link" href="{{ route('dashboard') }}">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    {{-- DATA BUKU --}}
    <div class="sidebar-heading">Data Buku</div>

    <li class="nav-item {{ request()->routeIs('buku.akademik') ? 'active' : '' }}">
        <a class="nav-link sidebar-link" href="{{ route('buku.akademik') }}">
            <i class="fas fa-file"></i>
            <span>Buku Akademik</span>
        </a>
    </li>

    <li class="nav-item {{ request()->routeIs('buku.non-akademik') ? 'active' : '' }}">
        <a class="nav-link sidebar-link" href="{{ route('buku.non-akademik') }}">
            <i class="fas fa-book"></i>
            <span>Buku Non-Akademik</span>
        </a>
    </li>

    {{-- KURIKULUM — khusus admin, kepperpus, kepsek --}}
    @if(in_array(Auth::user()->role, ['kep_perpus','admin','kepsek']))
        <hr class="sidebar-divider">
        <div class="sidebar-heading">Kurikulum</div>

        <li class="nav-item {{ request()->routeIs('paket.index') ? 'active' : '' }}">
            <a class="nav-link sidebar-link" href="{{ route('paket.index') }}">
                <i class="fas fa-layer-group"></i>
                <span>Paket Buku</span>
            </a>
        </li>

        <li class="nav-item {{ request()->routeIs('peminjaman.wajib.*') ? 'active' : '' }}">
            <a class="nav-link sidebar-link" href="{{ route('peminjaman.wajib.index') }}">
                <i class="fas fa-book-reader"></i>
                <span>Peminjaman Buku Wajib</span>
            </a>
        </li>
    @endif

    {{-- DATA USER — khusus admin, kepperpus, kepsek --}}
        <hr class="sidebar-divider">

        <div class="sidebar-heading">Data Users</div>

        {{-- Siswa --}}
        <li class="nav-item {{ request()->routeIs('users.siswa') ? 'active' : '' }}">
            <a class="nav-link sidebar-link" href="{{ route('users.siswa') }}">
                <i class="fas fa-user-graduate"></i>
                <span>Siswa</span>
            </a>
        </li>
        
    @if(in_array(Auth::user()->role, ['admin','kep_perpus','kepsek']))
        {{-- Guru --}}
        <li class="nav-item {{ request()->routeIs('users.guru') ? 'active' : '' }}">
            <a class="nav-link sidebar-link" href="{{ route('users.guru') }}">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Guru</span>
            </a>
        </li>

        {{-- Petugas --}}
        <li class="nav-item {{ request()->routeIs('petugas') ? 'active' : '' }}">
            <a class="nav-link sidebar-link" href="{{ route('petugas') }}">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Petugas</span>
            </a>
        </li>
    @endif

    <hr class="sidebar-divider">

    {{-- TRANSAKSI --}}
    <div class="sidebar-heading">Transaksi</div>

    <li class="nav-item {{ request()->routeIs('peminjaman') ? 'active' : '' }}">
        <a class="nav-link sidebar-link" href="{{ route('peminjaman') }}">
            <i class="fas fa-hand-holding"></i>
            <span>Peminjaman</span>
        </a>
    </li>

    <li class="nav-item {{ request()->routeIs('pengembalian') ? 'active' : '' }}">
        <a class="nav-link sidebar-link" href="{{ route('pengembalian') }}">
            <i class="fas fa-undo"></i>
            <span>Pengembalian</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    {{-- LOGOUT --}}
    <li class="nav-item">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-link btn btn-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </button>
        </form>
    </li>

</ul>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('accordionSidebar');
    const links = document.querySelectorAll('#accordionSidebar .sidebar-link');

    links.forEach(link => {
        link.addEventListener('click', () => {
            // hanya berlaku di layar kecil
            if (window.innerWidth < 768) {
                sidebar.classList.add('toggled');
            }
        });
    });
});
</script>