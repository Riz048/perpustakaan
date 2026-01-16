<?php

use Illuminate\Database\Query\Builder;

if (!function_exists('snapshot')) {
    function snapshot($q, $date, $mulai='tanggal_mulai', $selesai='tanggal_selesai')
    {
        if (!$date) return $q;

        return $q->where($mulai, '<=', $date)
                ->where(function ($x) use ($date, $selesai) {
                    $x->whereNull($selesai)
                    ->orWhere($selesai, '>=', $date);
                });
    }
}