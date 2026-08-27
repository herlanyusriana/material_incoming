<?php

namespace App\Exports;

use App\Models\NewSchema\Inventory\InventoryLocationStock;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LocationStockExport implements FromQuery, WithHeadings, WithMapping
{
    protected string $search;
    protected string $location;
    protected string $classification;
    protected bool $onlyPositive;

    public function __construct(string $search, string $location, string $classification, bool $onlyPositive)
    {
        $this->search = $search;
        $this->location = $location;
        $this->classification = $classification;
        $this->onlyPositive = $onlyPositive;
    }

    public function query()
    {
        $search = $this->search;
        $location = $this->location;
        $classification = $this->classification;
        $onlyPositive = $this->onlyPositive;

        return InventoryLocationStock::query()
            ->with(['gciPart'])
            ->when($onlyPositive, fn ($q) => $q->where('qty_on_hand', '>', 0))
            ->when($location !== '', fn ($q) => $q->where('location_code', $location))
            ->when(in_array($classification, ['RM', 'WIP', 'FG'], true), fn ($q) => $q->whereHas('gciPart', fn ($qg) => $qg->where('classification', $classification)))
            ->when($search !== '', function ($q) use ($search) {
                $s = strtoupper($search);
                $q->where(function ($qq) use ($s) {
                    $qq->whereHas('gciPart', function ($qg) use ($s) {
                        $qg->where('part_no', 'like', '%' . $s . '%')
                            ->orWhere('part_name', 'like', '%' . $s . '%');
                    })->orWhere('location_code', 'like', '%' . $s . '%');
                });
            })
            ->orderBy('location_code')
            ->orderBy('gci_part_id');
    }

    public function headings(): array
    {
        return [
            'Location',
            'Classification',
            'Part No',
            'Part Name',
            'Batch No',
            'Production Date',
            'Qty on Hand',
            'Updated At',
        ];
    }

    public function map($rec): array
    {
        return [
            $rec->location_code,
            $rec->gciPart?->classification ?? '',
            $rec->gciPart?->part_no ?? '',
            $rec->gciPart?->part_name ?? '',
            $rec->batch_no ?? '',
            $rec->production_date?->format('Y-m-d') ?? '',
            (float) $rec->qty_on_hand,
            $rec->updated_at?->format('Y-m-d H:i') ?? '',
        ];
    }
}
