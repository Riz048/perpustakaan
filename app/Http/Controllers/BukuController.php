<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\BukuEksemplar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    // Admin Akademik
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
                'eksemplar.riwayatStatus' => fn ($q) =>
                    $q->whereNull('tanggal_selesai')
            ])
            ->get()
            ->each(function ($buku) {
                $buku->jumlah_baik   = 0;
                $buku->jumlah_rusak  = 0;
                $buku->jumlah_hilang = 0;

                foreach ($buku->eksemplar as $e) {
                    $status = optional($e->riwayatStatus->first())->status;
                    if ($status === 'baik')   $buku->jumlah_baik++;
                    if ($status === 'rusak')  $buku->jumlah_rusak++;
                    if ($status === 'hilang') $buku->jumlah_hilang++;
                }
            });

        return view('admin.akademik', compact('buku'));
    }

    // Admin Non-Akademik
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
                'eksemplar.riwayatStatus' => fn ($q) =>
                    $q->whereNull('tanggal_selesai')
            ])
            ->get()
            ->each(function ($buku) {
                $buku->jumlah_baik   = 0;
                $buku->jumlah_rusak  = 0;
                $buku->jumlah_hilang = 0;

                foreach ($buku->eksemplar as $e) {
                    $status = optional($e->riwayatStatus->first())->status;
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

        $buku = Buku::create($data);

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
                'buku_id'        => $bukuId,
                'kode_eksemplar' => uniqid('EK-'),
                'status'         => $status,
            ]);

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
            'tipe_bacaan',
            'kode_buku',
            'judul',
            'nama_penerbit',
            'isbn',
            'pengarang',
            'jlh_hal',
            'tahun_terbit',
            'sinopsis',
            'keterangan',
        ]);

        if ($buku->kelas_akademik !== 'non-akademik' && $request->filled('kelas_akademik')) {
            $data['kelas_akademik'] = $request->kelas_akademik;
        }

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('cover_buku', 'public');
        }

        $buku->update($data);

        return back()->with('success', 'Data buku berhasil diperbarui');
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
