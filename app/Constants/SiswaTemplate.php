<?php

namespace App\Constants;

class SiswaTemplate
{
    public const COLUMNS = [
        'nama' => [
            'label' => 'nama',
            'required' => true,
        ],
        'username' => [
            'label' => 'username',
            'required' => true,
        ],
        'password' => [
            'label' => 'password',
            'required' => true,
        ],
        'kelamin' => [
            'label' => 'kelamin',
            'required' => true,
        ],
        'tingkat' => [
            'label' => 'tingkat',
            'required' => true,
        ],
        'rombel' => [
            'label' => 'rombel',
            'required' => true,
        ],
        'tahun_ajaran' => [
            'label' => 'tahun_ajaran',
            'required' => true,
        ],
        'semester' => [
            'label' => 'semester',
            'required' => true,
        ],
        'telpon' => [
            'label' => 'telpon',
            'required' => false,
        ],
        'alamat' => [
            'label' => 'alamat',
            'required' => false,
        ],
    ];
}
