<?php

namespace App\Http\Controllers;

use App\Models\BukuEksemplar;
use Illuminate\Http\Request;

class BukuEksemplarController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'buku_id' => 'required|exists:buku,id',
            'jumlah' => 'required|integer|min:1',
            'status' => 'required|in:baik,rusak,hilang',
        ]);

        for ($i = 1; $i <= $request->jumlah; $i++) {
            BukuEksemplar::create([
                'buku_id' => $request->buku_id,
                'kode_eksemplar' => uniqid('EK-'),
                'status' => $request->status
            ]);
        }

        return back()->with('success', 'Eksemplar berhasil ditambahkan');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:baik,rusak,hilang'
        ]);

        $eksemplar = BukuEksemplar::findOrFail($id);
        $eksemplar->update([
            'status' => $request->status
        ]);

        return back()->with('success', 'Status buku diperbarui');
    }
}
