<?php

namespace App\Exports;

use App\Models\StandardPacking;
use Maatwebsite\Excel\Concerns\FromCollection;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StandardPackingExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return StandardPacking::with('part.customer')->get();
    }

    public function headings(): array
    {
        return [
            'customer',
            'part_no',
            'part_name',
            'model',
            'classification',
            'status',
            'Del_class',
            'Packing qty',
            'Uom',
            'Trolly type',
            'Net Weight',
            'Kemasan',
        ];
    }

    public function map($row): array
    {
        return [
            $row->part->customer->name ?? '',
            $row->part->part_no,
            $row->part->part_name,
            $row->part->model,
            $row->part->classification,
            $row->status,
            $row->delivery_class,
            $row->packing_qty,
            $row->uom,
            $row->trolley_type,
            $row->net_weight,
            $row->kemasan,
        ];
    }
}
