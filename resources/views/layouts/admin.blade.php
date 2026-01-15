<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title', 'Perpustakaan')</title>

    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/logo_sman1_hamparan_perak.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css"/>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        :root {
            --primary: #001B5A99;
            --secondary: #001B5A5C;
            --bg: #FFFFFF;
            --card-shadow: 0 10px 25px rgba(3, 19, 45, 0.06);
        }
        body { background: var(--bg); font-family: "Inter", sans-serif; color: #0b1220; }
        .sidebar { background: linear-gradient(180deg, var(--secondary), var(--primary)); }
        .sidebar .nav-link:hover { background: rgba(255, 255, 255, 0.06); transform: translateX(6px); }
        .card { border: 0; border-radius: .8rem; box-shadow: var(--card-shadow); }
        table.dataTable thead th { background: rgba(0, 0, 0, 0.03); font-weight: 600; }
    </style>

    @yield('styles')
</head>

<body id="page-top">

    <div id="wrapper">
        @include('components.sidebar')
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">

                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 shadow sticky-top">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">{{ auth()->check() ? auth()->user()->username : 'User' }}</span>
                                
                                @php
                                    $foto = auth()->check() ? auth()->user()->foto : null;
                                @endphp
                                <img class="img-profile rounded-circle"
                                    src="{{ $foto && Storage::disk('public')->exists($foto) 
                                            ? asset('storage/' . $foto) 
                                            : asset('images/default-profile.png') }}"
                                    alt="{{ auth()->check() ? auth()->user()->username : 'User' }}">
                            </a>
                        </li>
                    </ul>
                </nav>
                @yield('konten')
                </div>

            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Perpustakaan 2025</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}"></script>
    <script src="{{ asset('js/sb-admin-2.min.js') }}"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

    @yield('script')

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('accordionSidebar');

        if (!sidebar) return;

        sidebar.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    sidebar.classList.add('toggled');
                }
            });
        });
    });
    </script>
</body>
</html>