<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Login — Perpustakaan</title>

    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet">

    <style>
    body {
        font-family: 'Inter', sans-serif !important;
        background: linear-gradient(135deg, #dbe7ff, #f7faff);
    }

    .card {
        border-radius: 25px;
        overflow: hidden;
        box-shadow: 0 12px 45px rgba(0, 0, 0, 0.15);
        background: #ffffffee;
        backdrop-filter: blur(6px);
    }

    .bg-login-image {
        background: url('/images/logo_sman1_hamparan_perak.png');
        background-repeat: no-repeat;
        background-position: center;
        background-size: 60%;
    }

    .login-title {
        font-size: 30px;
        font-weight: 700;
        color: #1f3a6f;
    }

    .form-control-user {
        border-radius: 12px !important;
        padding: 14px !important;
    }

    .btn-login {
        background: #60aaff;
        border-radius: 12px;
        padding: 13px 0;
        width: 100%;
        font-size: 16px;
        font-weight: 600;
        color: white;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">

                <div class="card border-0 my-5">
                    <div class="row">

                        {{-- GAMBAR SAMPING --}}
                        <div class="col-lg-6 bg-login-image"></div>

                        {{-- FORM LOGIN --}}
                        <div class="col-lg-6 p-5">

                            {{-- ALERT ERROR AUTO-HIDE --}}
                            @if ($errors->has('login'))
                            <div id="alert-error" class="mb-4 p-3 rounded text-white text-center"
                                style="background:#ff4444; font-weight:600;">
                                {{ $errors->first('login') }}
                            </div>
                            @endif

                            <div class="text-center mb-4">
                                <h1 class="login-title">Selamat Datang</h1>
                            </div>

                            {{-- FORM LOGIN --}}
                            <form method="POST" action="{{ route('authenticate') }}">
                                @csrf

                                {{-- USERNAME --}}
                                <div class="form-group">
                                    <input type="text" name="username" class="form-control form-control-user"
                                        placeholder="Masukkan Username" required autofocus>
                                </div>

                                {{-- PASSWORD --}}
                                <div class="form-group mt-3">
                                    <input type="password" name="password" class="form-control form-control-user"
                                        placeholder="Masukkan Password" required>
                                </div>

                                {{-- LOGIN BUTTON --}}
                                <button type="submit" class="btn-login mt-4 w-100">
                                    Login
                                </button>
                            </form>

                            <hr class="mt-4">

                            {{-- LUPA PASSWORD --}}
                            <!-- <div class="text-center">
                                <a class="small" href="#">Lupa Password?</a>
                            </div> -->

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

<script>
setTimeout(() => {
    const alert = document.getElementById('alert-error');
    if (alert) {
        alert.style.transition = 'opacity 0.6s ease';
        alert.style.opacity = '0';

        setTimeout(() => {
            alert.remove();
        }, 600);
    }
}, 3000);
</script>