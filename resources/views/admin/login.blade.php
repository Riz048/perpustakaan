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
            background: url('{{ asset("img/login-bg.jpg") }}');
            background-size: cover;
            background-position: center;
            filter: brightness(0.9);
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

                <div class="card o-hidden border-0 my-5">

                    <div class="row">

                        <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>

                        <div class="col-lg-6 p-5">

                            <div class="text-center mb-4">
                                <h1 class="login-title">Selamat Datang</h1>
                            </div>

                            {{-- FORM DUMMY TANPA CONTROLLER --}}
                            <form method="POST" action="#">
                                @csrf

                                <div class="form-group">
                                    <input type="email" name="email" class="form-control form-control-user"
                                        placeholder="Masukkan Email" required>
                                </div>

                                <div class="form-group mt-3">
                                    <input type="password" name="password" class="form-control form-control-user"
                                        placeholder="Masukkan Password" required>
                                </div>

                                <button type="submit" class="btn-login mt-4">Login</button>
                            </form>

                            <hr>

                            <!-- <div class="text-center">
                                <a class="small" href="#">Lupa Password?</a>
                            </div> -->

                            <div class="text-center">
                                <a class="small" href="register">Buat Akun Baru</a>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</body>

</html>
