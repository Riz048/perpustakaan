<?php

namespace App\Http\Controllers;

use App\Services\EksemplarService;
use App\Models\Buku;
use App\Models\BukuEksemplar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Imports\BukuAkademikImport;
use App\Exports\BukuAkademikTemplateExport;
use App\Imports\BukuNonAkademikImport;
use App\Exports\BukuNonAkademikTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class BukuController extends Controller
{
    // List buku
    public function index()
    {
        // Jika user login
        if (Auth::check()) {
            $role = Auth::user()->role;

            // Siswa
            if ($role === 'siswa') {

                $buku = Buku::where('kelas_akademik', '!=', 'non-akademik')
                    ->whereHas('eksemplar', function ($q) {
                        $q->whereHas('riwayatStatus', function ($r) {
                            $r->whereNull('tanggal_selesai')
                            ->where('status','baik');
                        })
                        ->whereDoesntHave('peminjamanDetail', function ($p) {
                            $p->where('status_transaksi', 'dipinjam');
                        });
                    })
                    ->withCount([
                        'eksemplar as buku_tersedia' => function ($q) {
                            $q->whereHas('riwayatStatus', function ($r) {
                                $r->whereNull('tanggal_selesai')
                                ->where('status','baik');
                            })
                            ->whereDoesntHave('peminjamanDetail', function ($p) {
                                $p->where('status_transaksi', 'dipinjam');
                            });
                        }
                    ])
                    ->get();
            }

            // Guru
            elseif ($role === 'guru') {
                $buku = Buku::all();
            } 
            // Role lain
            else {
                $buku = Buku::all();
            }

        } else {
            // Guest: hanya boleh lihat buku bacaan
            $buku = Buku::where('kelas_akademik', 'non-akademik')->get();
        }

        return view('user.beranda', compact('buku'));
    }

    // Detail buku
    public function detail($id)
    {
        $buku = Buku::withCount([
            'eksemplar as buku_tersedia' => function ($q) {
                $q->whereHas('riwayatStatus', function ($r) {
                    $r->whereNull('tanggal_selesai')
                    ->where('status','baik');
                })
                ->whereDoesntHave('peminjamanDetail', function ($p) {
                    $p->where('status_transaksi', 'dipinjam');
                });
            }
        ])->findOrFail($id);

        if ($buku->buku_tersedia < 1) {
            return redirect()
                ->back()
                ->withErrors('Buku tidak tersedia untuk dipinjam.');
        }

        return view('user.referensi.detail.detail-buku', compact('buku'));
    }

    // Akademik
    public function indexAkademik()
    {
        $buku = Buku::whereIn('kelas_akademik', ['10','11','12'])
            ->withCount([
                'eksemplar as buku_masuk',

                'eksemplar as buku_dipinjam' => function ($q) {
                    $q->whereHas('peminjamanDetail', fn ($p) =>
                        $p->where('status_transaksi','dipinjam')
                    );
                },

                'eksemplar as buku_tersedia' => function ($q) {
                    $q->whereHas('riwayatStatus', fn ($r) =>
                            $r->whereNull('tanggal_selesai')
                            ->where('status','baik')
                        )
                    ->whereDoesntHave('peminjamanDetail', fn ($p) =>
                            $p->where('status_transaksi','dipinjam')
                    );
                },
            ])
            ->with([
                'eksemplar.statusAktif' => fn ($q) =>
                    $q->whereNull('tanggal_selesai')
            ])
            ->get()
            ->each(function ($buku) {
                $buku->jumlah_baik   = 0;
                $buku->jumlah_rusak  = 0;
                $buku->jumlah_hilang = 0;

                foreach ($buku->eksemplar as $e) {
                    $status = optional($e->statusAktif)->status;

                    if ($status === 'baik')   $buku->jumlah_baik++;
                    if ($status === 'rusak')  $buku->jumlah_rusak++;
                    if ($status === 'hilang') $buku->jumlah_hilang++;
                }
            });

        return view('admin.akademik', compact('buku'));
    }

    // Non-Akademik
    public function indexNonAkademik()
    {
        $buku = Buku::where('kelas_akademik','non-akademik')
            ->withCount([
                'eksemplar as buku_masuk',

                'eksemplar as buku_dipinjam' => function ($q) {
                    $q->whereHas('peminjamanDetail', fn ($p) =>
                        $p->where('status_transaksi','dipinjam')
                    );
                },

                'eksemplar as buku_tersedia' => function ($q) {
                    $q->whereHas('riwayatStatus', fn ($r) =>
                            $r->whereNull('tanggal_selesai')
                            ->where('status','baik')
                        )
                    ->whereDoesntHave('peminjamanDetail', fn ($p) =>
                            $p->where('status_transaksi','dipinjam')
                    );
                },
            ])
            ->with([
                'eksemplar.statusAktif' => fn ($q) =>
                    $q->whereNull('tanggal_selesai')
            ])
            ->get()
            ->each(function ($buku) {
                $buku->jumlah_baik   = 0;
                $buku->jumlah_rusak  = 0;
                $buku->jumlah_hilang = 0;

                foreach ($buku->eksemplar as $e) {
                    $status = optional($e->statusAktif)->status;

                    if ($status === 'baik')   $buku->jumlah_baik++;
                    if ($status === 'rusak')  $buku->jumlah_rusak++;
                    if ($status === 'hilang') $buku->jumlah_hilang++;
                }
            });

        return view('admin.nonakademik', compact('buku'));
    }

    // Simpan buku baru
    public function store(Request $request)
    {
        $data = $request->except([
            'stok_baik',
            'stok_rusak',
            'stok_hilang'
        ]);

        // Atur tipe bacaan
        if (in_array($request->kelas_akademik, ['10', '11', '12'])) {
            $data['tipe_bacaan'] = 'non-fiksi';
        }

        if ($request->kelas_akademik === 'non-akademik') {
            $data['kelas_akademik'] = 'non-akademik';
            // tipe_bacaan boleh dari input
        }

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('cover_buku', 'public');
        }

        $request->validate([
            'kode_buku' => 'required|unique:buku,kode_buku',
        ], [
            'kode_buku.unique' => 'Kode buku sudah digunakan.',
        ]);

        try {
            $buku = Buku::create($data);
        } catch (\Illuminate\Database\QueryException $e) {

            if ($e->getCode() == 23000) {
                return back()
                    ->withErrors(['kode_buku' => 'Kode buku sudah ada di database.'])
                    ->withInput();
            }

            throw $e;
        }

        // Helper bikin eksemplar
        $this->buatEksemplar($buku->id, 'baik',   (int) $request->stok_baik);
        $this->buatEksemplar($buku->id, 'rusak',  (int) $request->stok_rusak);
        $this->buatEksemplar($buku->id, 'hilang', (int) $request->stok_hilang);

        return back()->with('success', 'Buku & stok berhasil ditambahkan');
    }

    // Helper bikin eksemplar
    private function buatEksemplar($bukuId, $status, $jumlah)
    {
        $service = app(EksemplarService::class);

        for ($i = 1; $i <= $jumlah; $i++) {

            $eksemplar = BukuEksemplar::create([
                'buku_id' => $bukuId,
                'kode_eksemplar' => uniqid('EK-'),
            ]);

            $service->ubahStatus(
                $eksemplar->id_eksemplar,
                $status,
                'tambah buku',
                auth()->id(),
                'Tambah eksemplar baru'
            );

            $service->ubahStatus(
                $eksemplar->id_eksemplar,
                $status,
                'tambah buku',
                auth()->id(),
                'Tambah eksemplar baru'
            );
        }
    }

    // Edit buku
    public function update(Request $request, $id)
    {
        $buku = Buku::findOrFail($id);

        $data = $request->only([
            'tipe_bacaan','kode_buku','judul','nama_penerbit',
            'isbn','pengarang','jlh_hal','tahun_terbit',
            'sinopsis','keterangan'
        ]);

        if ($request->hasFile('gambar')) {

            // hapus gambar lama
            if ($buku->gambar && Storage::disk('public')->exists($buku->gambar)) {
                Storage::disk('public')->delete($buku->gambar);
            }

            $data['gambar'] = $request->file('gambar')
                ->store('cover_buku', 'public');
        }

        $buku->update($data);

        try {
            DB::transaction(function () use ($request, $buku) {

                $current = [
                    'baik'   => $buku->jumlah_baik,
                    'rusak'  => $buku->jumlah_rusak,
                    'hilang' => $buku->jumlah_hilang,
                ];

                $target = [
                    'baik'   => (int) $request->stok_baik,
                    'rusak'  => (int) $request->stok_rusak,
                    'hilang' => (int) $request->stok_hilang,
                ];

                if (array_sum($current) !== array_sum($target)) {
                    throw new \Exception(
                        'Tidak bisa mengubah Total Buku / Buku Masuk'
                    );
                }

                $service = app(\App\Services\EksemplarService::class);

                foreach (['baik','rusak','hilang'] as $status) {

                    $diff = $target[$status] - $current[$status];
                    if ($diff <= 0) continue;

                    foreach (['baik','rusak','hilang'] as $ambilDari) {
                        if ($ambilDari === $status) continue;

                        $kelebihan = $current[$ambilDari] - $target[$ambilDari];
                        if ($kelebihan <= 0) continue;

                        $ambil = min($diff, $kelebihan);

                        $ids = BukuEksemplar::where('buku_id', $buku->id)
                            ->whereHas('riwayatStatus', fn ($q) =>
                                $q->whereNull('tanggal_selesai')
                                ->where('status', $ambilDari)
                            )
                            ->limit($ambil)
                            ->pluck('id_eksemplar');

                        foreach ($ids as $id) {
                            $service->ubahStatus(
                                $id,
                                $status,
                                'edit buku',
                                auth()->id(),
                                'Perubahan manual via edit buku'
                            );
                        }

                        $current[$ambilDari] -= $ambil;
                        $diff -= $ambil;

                        if ($diff <= 0) break;
                    }

                    if ($diff > 0) {
                        throw new \Exception(
                            'Jumlah eksemplar tidak mencukupi untuk perubahan status'
                        );
                    }
                }
            });

        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }

        return back()->with('success', 'Data buku & gambar berhasil diperbarui');
    }

    // Hapus buku
    public function destroy($id)
    {
        $buku = Buku::findOrFail($id);

        // Hapus cover jika ada
        if ($buku->gambar && Storage::disk('public')->exists($buku->gambar)) {
            Storage::disk('public')->delete($buku->gambar);
        }

        $buku->delete();

        return back()->with('success', 'Buku berhasil dihapus');
    }

    public function downloadTemplateBukuAkademik()
    {
        return Excel::download(
            new BukuAkademikTemplateExport,
            'template_import_buku_akademik.xlsx'
        );
    }

    public function importBukuAkademik(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx|max:2048'
        ]);

        $import = new BukuAkademikImport();
        Excel::import($import, $request->file('file'));

        if (!empty($import->errors)) {
            return back()->with('error_import', $import->errors);
        }

        return back()->with('success', 'Data buku akademik berhasil diimport');
    }

    public function downloadTemplateBukuNonAkademik()
    {
        return Excel::download(
            new BukuNonAkademikTemplateExport,
            'template_import_buku_non_akademik.xlsx'
        );
    }

    public function importBukuNonAkademik(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx|max:2048'
        ]);

        $import = new BukuNonAkademikImport();
        Excel::import($import, $request->file('file'));

        if (!empty($import->errors)) {
            return back()->with('error_import', $import->errors);
        }

        return back()->with('success', 'Data buku non-akademik berhasil diimport');
    }
}
