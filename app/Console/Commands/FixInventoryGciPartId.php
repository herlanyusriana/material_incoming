<?php

namespace App\Console\Commands;

use App\Models\LocationInventory;
use App\Models\Part;
use Illuminate\Console\Command;

class FixInventoryGciPartId extends Command
{
    protected $signature = 'inventory:fix-gci-part-id';
    protected $description = 'Fix LocationInventory records that have part_id but missing gci_part_id';

    public function handle()
    {
        $this->info('Finding LocationInventory records with missing gci_part_id...');

        $records = LocationInventory::whereNull('gci_part_id')
            ->whereNotNull('part_id')
            ->get();

        if ($records->isEmpty()) {
            $this->info('No records need fixing.');
            return 0;
        }

        $this->info("Found {$records->count()} records to fix.");

        $bar = $this->output->createProgressBar($records->count());
        $fixed = 0;
        $skipped = 0;

        foreach ($records as $record) {
            $part = Part::find($record->part_id);

            if ($part && $part->gci_part_id) {
                $record->gci_part_id = $part->gci_part_id;
                $record->save();
                $fixed++;
            } else {
                $this->warn("  Skipped record ID {$record->id}: Part {$record->part_id} has no gci_part_id");
                $skipped++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Fixed: {$fixed} records");
        $this->info("Skipped: {$skipped} records (vendor part has no gci_part_id)");

        return 0;
    }
}
