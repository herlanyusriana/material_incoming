<?php

namespace App\Console\Commands;

use App\Models\BomItem;
use App\Models\GciPart;
use Illuminate\Console\Command;

class LinkBomItems extends Command
{
    protected $signature = 'app:link-bom-items {--dry-run : Show what would be linked without making changes}';

    protected $description = 'Auto-link BOM items to GCI Parts (component & WIP)';

    public function handle(): void
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be made.');
        }

        $this->linkComponentParts($dryRun);
        $this->linkWipParts($dryRun);
    }

    private function linkComponentParts(bool $dryRun): void
    {
        $this->info('');
        $this->info('=== Component Parts ===');

        $items = BomItem::whereNull('component_part_id')
            ->where(function ($q) {
                $q->whereNotNull('component_part_no')->where('component_part_no', '!=', '');
            })
            ->get();

        $this->line("Found {$items->count()} unlinked component parts.");

        $linked = 0;
        $notFound = [];

        foreach ($items as $item) {
            $partNo = strtoupper(trim($item->component_part_no));
            $gciPart = GciPart::where('part_no', $partNo)->first();

            if ($gciPart) {
                if (!$dryRun) {
                    $item->update(['component_part_id' => $gciPart->id]);
                }
                $this->line("  BomItem#{$item->id} '{$partNo}' -> GciPart#{$gciPart->id}");
                $linked++;
            } else {
                $notFound[] = $partNo;
            }
        }

        $this->info("Linked: {$linked}");
        if (!empty($notFound)) {
            $this->warn("Not found in GCI Parts master (" . count($notFound) . "):");
            foreach (array_unique($notFound) as $pn) {
                $this->warn("  - {$pn}");
            }
        }
    }

    private function linkWipParts(bool $dryRun): void
    {
        $this->info('');
        $this->info('=== WIP Parts ===');

        $items = BomItem::whereNull('wip_part_id')
            ->where(function ($q) {
                $q->whereNotNull('wip_part_no')->where('wip_part_no', '!=', '');
            })
            ->get();

        $this->line("Found {$items->count()} unlinked WIP parts.");

        $linked = 0;
        $notFound = [];

        foreach ($items as $item) {
            $partNo = strtoupper(trim($item->wip_part_no));
            $gciPart = GciPart::where('part_no', $partNo)->first();

            if ($gciPart) {
                if (!$dryRun) {
                    $item->update(['wip_part_id' => $gciPart->id]);
                }
                $this->line("  BomItem#{$item->id} '{$partNo}' -> GciPart#{$gciPart->id}");
                $linked++;
            } else {
                $notFound[] = $partNo;
            }
        }

        $this->info("Linked: {$linked}");
        if (!empty($notFound)) {
            $this->warn("Not found in GCI Parts master (" . count($notFound) . "):");
            foreach (array_unique($notFound) as $pn) {
                $this->warn("  - {$pn}");
            }
        }
    }

}
