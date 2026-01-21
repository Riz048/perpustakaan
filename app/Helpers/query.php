<?php

use Illuminate\Database\Query\Builder;

if (!function_exists('snapshot')) {
    function snapshot(
        $q,
        $date,
        $mulai = 'tanggal_mulai',
        $selesai = 'tanggal_selesai',
        $userTable = null,
        $userStatusCol = 'status'
    ) {
        if ($date) {
            $q->where($mulai, '<=', $date)
              ->where(function ($x) use ($date, $selesai) {
                  $x->whereNull($selesai)
                    ->orWhere($selesai, '>=', $date);
              });
        }

        if ($userTable) {
            $q->where("{$userTable}.{$userStatusCol}", 'aktif');
        }

        return $q;
    }
}