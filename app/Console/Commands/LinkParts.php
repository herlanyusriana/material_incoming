<?php

namespace App\Console\Commands;

use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\VendorPart;
use Illuminate\Console\Command;

class LinkParts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:link-parts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link Vendor Parts to Internal GCI Parts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $parts = VendorPart::whereNull('gci_part_id')->get();
        $count = $parts->count();

        $this->info("Found {$count} orphaned Vendor Parts.");

        if ($count === 0) {
            return;
        }

        $linked = 0;
        $skipped = 0;

        foreach ($parts as $part) {
            /** @var VendorPart $part */
            $this->line("Processing: {$part->vendor_part_no} ({$part->vendor_part_name})");

            // Strategy 1: Find GCI Part by exact Part No match
            $gciPart = GciPart::where('part_no', $part->vendor_part_no)->first();

            // Strategy 2: Find by Name match (fuzzy)
            if (!$gciPart && $part->vendor_part_name) {
                $gciPart = GciPart::where('part_name', $part->vendor_part_name)->first();
            }

            if ($gciPart) {
                $part->update(['gci_part_id' => $gciPart->id]);
                $this->info("  -> Linked to existing GCI Part: {$gciPart->part_no}");
                $linked++;
            } else {
                $this->warn("  -> No match found. Register this part in Parts Master first.");
                $skipped++;
            }
        }

        $this->info("Done! Linked: {$linked}, Skipped: {$skipped}");
    }
}
