<?php

namespace App\Console\Commands;

use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\NewSchema\Core\GciPart;
use Illuminate\Console\Command;

class FixInventoryGciPartId extends Command
{
    protected $signature = 'inventory:fix-gci-part-id';
    protected $description = 'Verify InventoryLocationStock records have valid gci_part_id references';

    public function handle()
    {
        $this->info('Verifying InventoryLocationStock records...');

        // Check for any records with invalid gci_part_id references
        $records = InventoryLocationStock::query()
            ->whereNotExists(function ($query) {
                $query->select('id')
                    ->from('gci_parts')
                    ->whereColumn('gci_parts.id', 'inventory_location_stock.gci_part_id');
            })
            ->get();

        if ($records->isEmpty()) {
            $this->info('All records have valid gci_part_id references.');
            return 0;
        }

        $this->warn("Found {$records->count()} records with invalid gci_part_id references.");

        foreach ($records as $record) {
            $this->warn("  Record ID {$record->id}: gci_part_id={$record->gci_part_id} (not found in gci_parts)");
        }

        return 0;
    }
}
