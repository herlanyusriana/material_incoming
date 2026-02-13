<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StandardPackingTemplateExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return new Collection();
    }

    public function headings(): array
    {
        return [
            'customer',
            'part_no',
            'qty',
            'del_class',
            'trolley_type',
            'uom',
            'net_weight',
            'gross_weight',
        ];
    }
}

