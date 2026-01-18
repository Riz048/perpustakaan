<?php

namespace App\Exports;

use App\Constants\SiswaTemplate;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SiswaTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return collect(SiswaTemplate::COLUMNS)
            ->pluck('label')
            ->toArray();
    }

    public function array(): array
    {
        return [];
    }
}
