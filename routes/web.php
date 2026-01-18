<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BukuController;
use App\Http\Controllers\PaketBukuController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\PengembalianController;
use App\Http\Controllers\PeminjamanWajibController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\ReferensiController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PengaturanController;
use App\Http\Middleware\RoleMiddleware;

// Publik

Route::get('/', [BukuController::class, 'index'])->name('beranda');
Route::get('/detail-buku/{id}', [BukuController::class, 'detail'])->name('detail.buku');
Route::get('/kategori/{tipe}', [CategoryController::class, 'show'])->name('user.kategori.show');

Route::view('/novel', 'user.referensi.kategori.fiksi.novel')->name('user.kategori.novel');
Route::view('/komik', 'user.referensi.kategori.fiksi.komik')->name('user.kategori.komik');
Route::view('/mitos', 'user.referensi.kategori.fiksi.mitos')->name('user.kategori.mitos');
Route::view('/fabel', 'user.referensi.kategori.fiksi.fabel')->name('user.kategori.fabel');
Route::view('/cerpen', 'user.referensi.kategori.fiksi.cerpen')->name('user.kategori.cerpen');
Route::view('/legenda', 'user.referensi.kategori.fiksi.legenda')->name('user.kategori.legenda');

Route::view('/ilmu-sosial', 'user.referensi.kategori.nonfiksi.sosial')->name('buku.ilmu-sosial');
Route::view('/ilmu-terapan', 'user.referensi.kategori.nonfiksi.terapan')->name('buku.ilmu-terapan');
Route::view('/ilmu-murni', 'user.referensi.kategori.nonfiksi.murni')->name('buku.ilmu-murni');
Route::view('/bahasa', 'user.referensi.kategori.nonfiksi.bahasa')->name('buku.bahasa');
Route::view('/geografi-sejarah', 'user.referensi.kategori.nonfiksi.geosejarah')->name('buku.geografi-sejarah');
Route::view('/ilmu-agama', 'user.referensi.kategori.nonfiksi.agama')->name('buku.ilmu-agama');

Route::get('/referensi', [ReferensiController::class, 'home'])->name('user.referensi.home');
Route::get('/referensi/{kategori}', [ReferensiController::class, 'kategori'])->name('user.referensi.kategori');


// Auth

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('authenticate');

    Route::get('/admin', fn() => redirect()->route('login'));
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');


// Autentikasi

Route::middleware(['auth'])->group(function () {

    // User
    Route::middleware([RoleMiddleware::class . ':siswa,guru,petugas,kep_perpus,kepsek'])->group(function () {
        Route::get('/riwayat', [UserController::class, 'riwayat'])->name('user.transaksi.riwayat');
    });

    // Staff
    Route::middleware([RoleMiddleware::class . ':admin,kep_perpus,kepsek,petugas'])->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        // Users
        Route::get('/users/siswa', [UserController::class, 'siswa'])->name('users.siswa');
        Route::get('/users/guru', [UserController::class, 'guru'])->name('users.guru');

        // CRUD buku
        Route::get('/buku-akademik', [BukuController::class, 'indexAkademik'])->name('buku.akademik');
        Route::get('/buku-non-akademik', [BukuController::class, 'indexNonAkademik'])->name('buku.non-akademik');
        Route::post('/buku', [BukuController::class, 'store'])->name('buku.store');
        Route::put('/buku/{id}', [BukuController::class, 'update'])->name('buku.update');
        Route::delete('/buku/{id}', [BukuController::class, 'destroy'])->name('buku.destroy');

        // CRUD user (siswa/guru)
        Route::resource('users', UserController::class)
            ->except(['index','create','edit','show'])
            ->names([
                'store'   => 'users.store',
                'update'  => 'users.update',
                'destroy' => 'users.destroy',
            ]);

        // Peminjaman
        Route::resource('peminjaman', PeminjamanController::class)->except(['create', 'edit', 'show'])->names([
            'index'   => 'peminjaman',
            'store'   => 'peminjaman.store',
            'update'  => 'peminjaman.update',
            'destroy' => 'peminjaman.destroy',
        ]);

        // Pengembalian
        Route::resource('pengembalian', PengembalianController::class)->except(['create', 'edit', 'update', 'show'])->names([
            'index'   => 'pengembalian',
            'store'   => 'pengembalian.store',
            'destroy' => 'pengembalian.destroy',
        ]);

        Route::put('/pengembalian/{id}', [PengembalianController::class, 'update'])
            ->name('pengembalian.update');

        Route::get('pengembalian/paket/{id}', [PengembalianController::class, 'getDetailPaket'])
            ->name('pengembalian.paket.detail');
        Route::get('/pengembalian/paket/edit/{id}', [PengembalianController::class, 'getEditPaket']);
        Route::put('/pengembalian/paket/{id}', [PengembalianController::class, 'updatePaket']);
    });

    // Admin
    Route::middleware([RoleMiddleware::class . ':admin,kep_perpus,kepsek'])->group(function () {
        Route::resource('petugas', PetugasController::class)
            ->except(['create', 'edit', 'show'])
            ->names([
                'index'   => 'petugas',
                'store'   => 'petugas.store',
                'update'  => 'petugas.update',
                'destroy' => 'petugas.destroy',
            ]);
    });

    // Export PDF
    Route::post('/dashboard/export/pdf', [DashboardController::class, 'exportPdf'])
        ->middleware('auth', RoleMiddleware::class.':admin,kepsek,kep_perpus')
        ->name('dashboard.export.pdf');

    // Paket Buku (Kurikulum) — khusus kepperpus, admin, kepsek
    Route::middleware([RoleMiddleware::class . ':kep_perpus,admin,kepsek'])->group(function () {
        Route::get('/paket-buku', [PaketBukuController::class, 'index'])->name('paket.index');
        Route::get('/paket-buku/create', [PaketBukuController::class, 'create'])->name('paket.create');
        Route::post('/paket-buku', [PaketBukuController::class, 'store'])->name('paket.store');
        Route::get('/paket-buku/{id}/edit', [PaketBukuController::class, 'edit'])->name('paket.edit');
        Route::put('/paket-buku/{id}', [PaketBukuController::class, 'update'])->name('paket.update');
        Route::patch('/paket-buku/{id}/toggle', [PaketBukuController::class, 'toggleStatus'])->name('paket.toggle');
        Route::get('/kurikulum/peminjaman', [PeminjamanWajibController::class, 'index'])->name('peminjaman.wajib.index');
        Route::post('/kurikulum/peminjaman', [PeminjamanWajibController::class, 'store'])->name('peminjaman.wajib.store');
    });

    Route::middleware([RoleMiddleware::class . ':kep_perpus,admin,kepsek'])->group(function () {
        Route::get('/template/siswa', [UserController::class, 'downloadTemplateSiswa'])->name('template.siswa');
        Route::post('/users/import-siswa', [UserController::class, 'importSiswa'])->name('users.import.siswa');
        Route::get('/template/guru', [UserController::class, 'downloadTemplateGuru'])->name('template.guru');
        Route::post('/users/import-guru', [UserController::class, 'importGuru'])->name('users.import.guru');
    });
});

