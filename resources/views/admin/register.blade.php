{{-- Converted from admin/register.html to Blade --}}
{{-- File: resources/views/admin/register.blade.php --}}

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <title>Register — SMAN 1 Hamparan Perak</title>

  {{-- === FIXED: Panggil CSS dari public/css === --}}
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

  <style>
    :root{
      --accent:#63a4ff;
      --accent2:#87c3ff;
      --text-dark:#0d223d;
      --text-muted:#6e7a89;
      --radius:14px;
      --shadow:0 10px 40px rgba(0,0,0,0.08);
    }

    body{
      margin:0;
      padding:40px 16px;
      min-height:100vh;
      font-family:'Inter', sans-serif;
      background:linear-gradient(180deg,#eaf3ff 0%,#f8fbff 40%,#ffffff 100%);
      display:flex;
      align-items:center;
      justify-content:center;
      overflow-x:hidden;
    }

    body::before, body::after{
      content:"";
      position:fixed;
      border-radius:50%;
      filter:blur(45px);
      opacity:.18;
    }
    body::before{
      width:420px;height:420px;
      background:#8ab3ff;
      top:-80px; left:-60px;
    }
    body::after{
      width:400px;height:400px;
      background:#b6d7ff;
      bottom:-80px; right:-60px;
    }

    .panel{
      width:100%;
      max-width:620px;
      background:rgba(255,255,255,0.55);
      backdrop-filter:blur(12px) saturate(140%);
      border:1px solid rgba(255,255,255,0.4);
      border-radius:24px;
      padding:45px 55px 50px;
      text-align:center;
      box-shadow:var(--shadow);
    }

    .logo{
      width:95px;height:95px;
      margin:0 auto 14px;
      background:white;
      border-radius:18px;
      display:flex;
      align-items:center;
      justify-content:center;
      overflow:hidden;
      box-shadow:0 8px 22px rgba(70,100,150,0.15);
    }
    .logo img{
      width:100%;height:100%;object-fit:cover;
    }

    .title{ font-size:24px;font-weight:800;color:var(--text-dark);letter-spacing:0.3px;margin-bottom:4px;text-transform:uppercase; }
    .sub{ color:var(--text-muted);font-size:13px;margin-bottom:30px; }

    .input{
      width:92%;
      padding:15px 16px;
      font-size:14px;
      border-radius:var(--radius);
      border:1px solid rgba(80,110,140,.12);
      background:rgba(255,255,255,0.88);
      margin-bottom:20px;
      transition:.15s;
    }
    .input:focus{
      outline:none;
      border-color:var(--accent);
      box-shadow:0 0 0 3px rgba(99,164,255,0.22);
      background:white;
    }

    textarea.input{ min-height:90px; resize:vertical; }

    .row{ display:flex; gap:20px;margin-bottom:4px; }
    .col{ flex:1; }

    label{ display:block;margin-bottom:6px;font-size:13px;text-align:left;color:var(--text-muted); }

    .btn{
      width:100%;
      border:none;
      padding:15px;
      margin-top:8px;
      border-radius:var(--radius);
      font-size:15px;
      font-weight:700;
      color:white;
      cursor:pointer;
      background:linear-gradient(135deg,var(--accent),var(--accent2));
      box-shadow:0 12px 28px rgba(90,150,255,.28);
      transition:.15s;
    }
    .btn:hover{ transform:translateY(-2px);box-shadow:0 16px 35px rgba(90,150,255,.33); }

    .bottom{ margin-top:20px;font-size:14px;color:var(--text-muted); }
    .bottom a{ color:var(--accent);text-decoration:none;font-weight:600; }

    .date-row{ margin-bottom:22px;margin-top:8px; }
  </style>
</head>

<body>

<div class="panel">

  {{-- === FIXED: LOGO === --}}
  <div class="logo">
    <img src="{{ asset('img/logo.png') }}" alt="logo">
  </div>

  <div class="title">SMAN 1 HAMPARAN PERAK</div>
  <div class="sub">Sistem Perpustakaan — Formulir Pendaftaran</div>

  <form>

    <input type="text" class="input" placeholder="Nama Lengkap">
    <input type="text" class="input" placeholder="Username">

    <div class="row">
      <div class="col">
        <input type="text" class="input" placeholder="Tempat Lahir">
      </div>
      <div class="col">
        <input type="tel" class="input" placeholder="Nomor Telpon">
      </div>
    </div>

    <div class="row date-row">
      <div class="col">
        <label>Tanggal Lahir</label>
        <input type="date" class="input">
      </div>
      <div class="col">
        <label>Jenis Kelamin</label>
        <select class="input">
          <option value="" disabled selected hidden>Pria / Wanita</option>
          <option>Pria</option>
          <option>Wanita</option>
        </select>
      </div>
    </div>

    <textarea class="input" placeholder="Alamat Lengkap"></textarea>

    <div class="row">
      <div class="col">
        <input type="password" class="input" placeholder="Password">
      </div>
      <div class="col">
        <input type="password" class="input" placeholder="Ulangi Password">
      </div>
    </div>

    <button class="btn">Register Account</button>

    <div class="bottom">
      Already have an account?
      <a href="{{ route('login') }}">Login!</a>
    </div>

  </form>

</div>

</body>
</html>
