<?php

namespace App\Constants;

class BukuAkademikTemplate
{
    public const COLUMNS = [

        'kelas_akademik' => [
            'label'    => 'kelas_akademik',
            'required' => true,
            'allowed'  => ['10', '11', '12'],
            'note'     => 'Isi: 10 / 11 / 12',
        ],

        'kode_buku' => [
            'label'    => 'kode_buku',
            'required' => true,
        ],

        'judul' => [
            'label'    => 'judul',
            'required' => true,
        ],

        'nama_penerbit' => [
            'label'    => 'nama_penerbit',
            'required' => true,
        ],

        'isbn' => [
            'label'    => 'isbn',
            'required' => true,
        ],

        'pengarang' => [
            'label'    => 'pengarang',
            'required' => true,
        ],

        'tahun_terbit' => [
            'label'    => 'tahun_terbit',
            'required' => true,
            'format'   => 'YYYY',
        ],

        'tahun_masuk' => [
            'label'    => 'tahun_masuk',
            'required' => true,
            'format'   => 'YYYY',
        ],

        'stok_baik' => [
            'label'    => 'stok_baik',
            'required' => true,
            'type'     => 'integer',
            'min'      => 0,
        ],

        'stok_rusak' => [
            'label'    => 'stok_rusak',
            'required' => true,
            'type'     => 'integer',
            'min'      => 0,
        ],

        'stok_hilang' => [
            'label'    => 'stok_hilang',
            'required' => true,
            'type'     => 'integer',
            'min'      => 0,
        ],

        'sinopsis' => [
            'label'    => 'sinopsis',
            'required' => false,
        ],

        'keterangan' => [
            'label'    => 'keterangan',
            'required' => false,
        ],
    ];
}
