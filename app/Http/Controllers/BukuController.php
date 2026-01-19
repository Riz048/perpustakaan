<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\BukuEksemplar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
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
                $kelas = Auth::user()->kelas_akademik ?? null;

                $buku = Buku::where('kelas_akademik', '!=', 'non-akademik')
                    ->whereHas('eksemplar', function ($q) {
                        $q->where('status', 'baik')
                        ->whereDoesntHave('peminjamanDetail', function ($p) {
                            $p->where('status_transaksi', 'dipinjam');
                        });
                    })
                    ->withCount([
                        'eksemplar as buku_tersedia' => function ($q) {
                            $q->where('status', 'baik')
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
                $q->where('status', 'baik')
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
        $buku = Buku::whereIn('kelas_akademik', ['10', '11', '12'])
            ->withCount([
                'eksemplar as buku_masuk',
                'eksemplar as buku_tersedia' => function ($q) {
                    $q->where('status', 'baik')
                    ->whereDoesntHave('peminjamanDetail', function ($p) {
                        $p->where('status_transaksi', 'dipinjam');
                    });
                },
                'eksemplar as buku_dipinjam' => function ($q) {
                    $q->whereHas('peminjamanDetail', function ($p) {
                        $p->where('status_transaksi', 'dipinjam');
                    });
                },
                'eksemplar as jumlah_baik'   => fn ($q) => $q->where('status', 'baik'),
                'eksemplar as jumlah_rusak'  => fn ($q) => $q->where('status', 'rusak'),
                'eksemplar as jumlah_hilang' => fn ($q) => $q->where('status', 'hilang'),
            ])
            ->get();

        return view('admin.akademik', compact('buku'));
    }

    // Admin Non-Akademik
    public function indexNonAkademik()
    {
        $buku = Buku::where('kelas_akademik', 'non-akademik')
            ->withCount([
                'eksemplar as buku_masuk',
                'eksemplar as buku_tersedia' => function ($q) {
                    $q->where('status', 'baik')
                    ->whereDoesntHave('peminjamanDetail', function ($p) {
                        $p->where('status_transaksi', 'dipinjam');
                    });
                },
                'eksemplar as buku_dipinjam' => function ($q) {
                    $q->whereHas('peminjamanDetail', function ($p) {
                        $p->where('status_transaksi', 'dipinjam');
                    });
                },
                'eksemplar as jumlah_baik'   => fn ($q) => $q->where('status', 'baik'),
                'eksemplar as jumlah_rusak'  => fn ($q) => $q->where('status', 'rusak'),
                'eksemplar as jumlah_hilang' => fn ($q) => $q->where('status', 'hilang'),
            ])
            ->get();

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
        for ($i = 1; $i <= $jumlah; $i++) {
            BukuEksemplar::create([
                'buku_id' => $bukuId,
                'kode_eksemplar' => uniqid('EK-'),
                'status' => $status
            ]);
        }
    }

    private function syncEksemplar($bukuId, $status, $lama, $baru)
    {
        if ($baru > $lama) {
            $tambah = $baru - $lama;

            for ($i = 1; $i <= $tambah; $i++) {
                BukuEksemplar::create([
                    'buku_id' => $bukuId,
                    'kode_eksemplar' => uniqid('EK-'),
                    'status' => $status
                ]);
            }
        }

        if ($baru < $lama) {
            $kurang = $lama - $baru;

            BukuEksemplar::where('buku_id', $bukuId)
                ->where('status', $status)
                ->limit($kurang)
                ->delete();
        }
    }

    // Edit buku
    public function update(Request $request, $id)
    {
        $buku = Buku::findOrFail($id);

        DB::transaction(function () use ($request, $buku) {

            $data = [
                'tipe_bacaan'    => $request->tipe_bacaan ?? $buku->tipe_bacaan,
                'kode_buku'      => $request->kode_buku ?? $buku->kode_buku,
                'judul'          => $request->judul ?? $buku->judul,
                'nama_penerbit'  => $request->nama_penerbit ?? $buku->nama_penerbit,
                'isbn'           => $request->isbn ?? $buku->isbn,
                'pengarang'      => $request->pengarang ?? $buku->pengarang,
                'jlh_hal'        => $request->jlh_hal ?? $buku->jlh_hal,
                'tahun_terbit'   => $request->tahun_terbit ?? $buku->tahun_terbit,
                'sinopsis'       => $request->sinopsis ?? $buku->sinopsis,
                'keterangan'     => $request->keterangan ?? $buku->keterangan,
            ];

            if ($buku->kelas_akademik !== 'non-akademik' && $request->filled('kelas_akademik')) {
                $data['kelas_akademik'] = $request->kelas_akademik;
            }

            if ($request->hasFile('gambar')) {
                $data['gambar'] = $request->file('gambar')->store('cover_buku', 'public');
            }

            $buku->update($data);

            $lamaBaik   = $buku->eksemplar()->where('status', 'baik')->count();
            $lamaRusak  = $buku->eksemplar()->where('status', 'rusak')->count();
            $lamaHilang = $buku->eksemplar()->where('status', 'hilang')->count();

            $baruBaik   = (int) ($request->stok_baik ?? $lamaBaik);
            $baruRusak  = (int) ($request->stok_rusak ?? $lamaRusak);
            $baruHilang = (int) ($request->stok_hilang ?? $lamaHilang);

            $this->syncEksemplar($buku->id, 'baik', $lamaBaik, $baruBaik);
            $this->syncEksemplar($buku->id, 'rusak', $lamaRusak, $baruRusak);
            $this->syncEksemplar($buku->id, 'hilang', $lamaHilang, $baruHilang);
        });

        return back()->with('success', 'Data & stok buku berhasil diperbarui');
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
