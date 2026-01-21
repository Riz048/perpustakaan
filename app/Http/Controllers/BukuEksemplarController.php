<?php

namespace App\Http\Controllers;

use App\Services\StatusBukuService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\BukuEksemplar;

class BukuEksemplarController extends Controller
{
    public function store(Request $request, StatusBukuService $service)
    {
        $request->validate([
            'buku_id' => 'required|exists:buku,id',
            'jumlah'  => 'required|integer|min:1',
            'status'  => 'required|in:baik,rusak,hilang',
        ]);

        DB::transaction(function () use ($request, $service) {

            for ($i = 1; $i <= $request->jumlah; $i++) {

                $eksemplar = BukuEksemplar::create([
                    'buku_id'        => $request->buku_id,
                    'kode_eksemplar' => uniqid('EK-'),
                ]);

                $service->ubahManual(
                    $eksemplar->id_eksemplar,
                    $request->status,
                    auth()->id(),
                    'Tambah eksemplar baru'
                );
            }
        });

        return back()->with(
            'success',
            'Eksemplar berhasil ditambahkan dan status dicatat.'
        );
    }
}
