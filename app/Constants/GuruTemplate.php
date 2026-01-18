<?php

namespace App\Constants;

class GuruTemplate
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
