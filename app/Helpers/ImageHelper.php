<?php

use Illuminate\Support\Facades\Storage;

if (! function_exists('bookImage')) {
    function bookImage($path)
    {
        // Jika kosong → fallback
        if (!$path) {
            return asset('images/buku.png');
        }

        // Jika URL
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // Jika path Windows lokal → fallback
        if (preg_match('/^[A-Za-z]:\\\\/', $path)) {
            return asset('images/buku.png');
        }

        // Jika file storage valid
        if (Storage::disk('public')->exists($path)) {
            return asset('storage/'.$path);
        }

        // Fallback terakhir
        return asset('images/buku.png');
    }
}