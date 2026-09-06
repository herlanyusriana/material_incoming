<?php

namespace App\Exports;

use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockCardExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected string $search;
    protected string $classification;

    public function __construct(string $search, string $classification)
    {
        $this->search = $search;
        $this->classification = $classification;
    }

    public function collection(): Collection
    {
        $classification = in_array($this->classification, ['RM', 'WIP', 'FG'], true) ? $this->classification : '';
        $search = trim($this->search);

        $searchClause = function ($query) use ($search) {
            $s = strtoupper($search);
            $query->where('part_no', 'like', '%' . $s . '%')
                ->orWhere('part_name', 'like', '%' . $s . '%')
                ->orWhere('model', 'like', '%' . $s . '%');
        };

        $rows = [];

        // RM + WIP + FG semuanya dari inventory_location_stock (single source of truth)
        $query = InventoryLocationStock::query()
            ->whereNotNull('gci_part_id')
            ->join('gci_parts as gp', 'gp.id', '=', 'inventory_location_stock.gci_part_id')
            ->when($classification === '', fn ($q) => $q->whereIn('gp.classification', ['RM', 'WIP', 'FG']))
            ->when($classification !== '', fn ($q) => $q->where('gp.classification', $classification))
            ->when($search !== '', fn ($q) => $q->where($searchClause))
            ->selectRaw('inventory_location_stock.gci_part_id, gp.classification, gp.part_no, gp.part_name, gp.model, gp.subcount_uom as uom, SUM(inventory_location_stock.qty_on_hand) as total_qty')
            ->groupBy('inventory_location_stock.gci_part_id', 'gp.classification', 'gp.part_no', 'gp.part_name', 'gp.model', 'gp.subcount_uom')
            ->get();

        foreach ($query as $row) {
            $rows[] = [
                'part_no' => $row->part_no,
                'part_name' => $row->part_name,
                'model' => $row->model,
                'classification' => $row->classification,
                'uom' => $row->uom,
                'qty' => (float) $row->total_qty,
            ];
        }

        usort($rows, function ($a, $b) {
            $catCmp = $a['classification'] <=> $b['classification'];
            return $catCmp !== 0 ? $catCmp : strcmp($a['part_no'], $b['part_no']);
        });

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'Part No',
            'Part Name',
            'Model',
            'Classification',
            'UOM',
            'Saldo (On Hand)',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 32,
            'C' => 20,
            'D' => 16,
            'E' => 10,
            'F' => 18,
        ];
    }
}
