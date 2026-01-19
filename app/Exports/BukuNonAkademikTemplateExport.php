<?php

namespace App\Exports;

use App\Constants\BukuNonAkademikTemplate;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BukuNonAkademikTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return array_keys(BukuNonAkademikTemplate::COLUMNS);
    }

    public function array(): array
    {
        return [];
    }
}
