<?php

namespace App\Exports;

use App\Constants\BukuAkademikTemplate;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromArray;

class BukuAkademikTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return array_keys(BukuAkademikTemplate::COLUMNS);
    }

    public function array(): array
    {
        return [];
    }
}
