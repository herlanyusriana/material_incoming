<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairPartRelations extends Command
{
    protected $signature = 'parts:repair-links
        {--dry-run : Simulate changes without writing}
        {--purge-orphans : Delete orphan rows in inventories}';

    protected $description = 'Repair legacy part_id relations to gci_part_vendor/gci_part_id and re-sync inventory summaries.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $purgeOrphans = (bool) $this->option('purge-orphans');

        $this->info('Starting part relation repair' . ($dryRun ? ' (dry-run)' : ''));

        $map = DB::table('gci_part_vendor')->pluck('gci_part_id', 'id');
        $this->line('Loaded vendor-part links: ' . $map->count());

        DB::transaction(function () use ($map, $dryRun, $purgeOrphans): void {
            $this->repairArrivalItems($map, $dryRun);
            $this->repairLocationInventory($map, $dryRun);
            $this->repairLocationInventoryAdjustments($map, $dryRun);
            $this->resyncInventorySummaries($dryRun, $purgeOrphans);
        });

        $this->newLine();
        $this->info('Part relation repair finished.');

        return self::SUCCESS;
    }

    private function repairArrivalItems($map, bool $dryRun): void
    {
        if (!DB::getSchemaBuilder()->hasTable('arrival_items')) {
            return;
        }

        $fixed = 0;
        $orphans = 0;

        DB::table('arrival_items')
            ->select('id', 'part_id', 'gci_part_id', 'gci_part_vendor_id')
            ->whereNotNull('part_id')
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use ($map, $dryRun, &$fixed, &$orphans): void {
                foreach ($rows as $row) {
                    $gciPartId = $map[$row->part_id] ?? null;
                    if (!$gciPartId) {
                        $orphans++;
                        continue;
                    }

                    $update = [];
                    if ((int) ($row->gci_part_id ?? 0) !== (int) $gciPartId) {
                        $update['gci_part_id'] = $gciPartId;
                    }
                    if ((int) ($row->gci_part_vendor_id ?? 0) !== (int) $row->part_id) {
                        $update['gci_part_vendor_id'] = $row->part_id;
                    }

                    if (!empty($update)) {
                        $fixed++;
                        if (!$dryRun) {
                            DB::table('arrival_items')->where('id', $row->id)->update($update);
                        }
                    }
                }
            });

        $this->line("arrival_items: fixed={$fixed}, orphan_part_links={$orphans}");
    }

    private function repairLocationInventory($map, bool $dryRun): void
    {
        if (!DB::getSchemaBuilder()->hasTable('location_inventory')) {
            return;
        }

        $fixed = 0;
        $orphans = 0;

        DB::table('location_inventory')
            ->select('id', 'part_id', 'gci_part_id')
            ->whereNotNull('part_id')
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use ($map, $dryRun, &$fixed, &$orphans): void {
                foreach ($rows as $row) {
                    $gciPartId = $map[$row->part_id] ?? null;
                    if (!$gciPartId) {
                        $orphans++;
                        continue;
                    }

                    if ((int) ($row->gci_part_id ?? 0) !== (int) $gciPartId) {
                        $fixed++;
                        if (!$dryRun) {
                            DB::table('location_inventory')->where('id', $row->id)->update(['gci_part_id' => $gciPartId]);
                        }
                    }
                }
            });

        $this->line("location_inventory: fixed={$fixed}, orphan_part_links={$orphans}");
    }

    private function repairLocationInventoryAdjustments($map, bool $dryRun): void
    {
        if (!DB::getSchemaBuilder()->hasTable('location_inventory_adjustments')) {
            return;
        }

        if (!DB::getSchemaBuilder()->hasColumn('location_inventory_adjustments', 'part_id')
            || !DB::getSchemaBuilder()->hasColumn('location_inventory_adjustments', 'gci_part_id')) {
            return;
        }

        $fixed = 0;
        $orphans = 0;

        DB::table('location_inventory_adjustments')
            ->select('id', 'part_id', 'gci_part_id')
            ->whereNotNull('part_id')
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use ($map, $dryRun, &$fixed, &$orphans): void {
                foreach ($rows as $row) {
                    $gciPartId = $map[$row->part_id] ?? null;
                    if (!$gciPartId) {
                        $orphans++;
                        continue;
                    }

                    if ((int) ($row->gci_part_id ?? 0) !== (int) $gciPartId) {
                        $fixed++;
                        if (!$dryRun) {
                            DB::table('location_inventory_adjustments')->where('id', $row->id)->update(['gci_part_id' => $gciPartId]);
                        }
                    }
                }
            });

        $this->line("location_inventory_adjustments: fixed={$fixed}, orphan_part_links={$orphans}");
    }

    private function resyncInventorySummaries(bool $dryRun, bool $purgeOrphans): void
    {
        if (!DB::getSchemaBuilder()->hasTable('location_inventory') || !DB::getSchemaBuilder()->hasTable('inventories')) {
            return;
        }

        $vendorSums = DB::table('location_inventory')
            ->select('part_id', DB::raw('SUM(qty_on_hand) as on_hand'))
            ->whereNotNull('part_id')
            ->groupBy('part_id')
            ->get();

        $upserts = 0;
        foreach ($vendorSums as $row) {
            $upserts++;
            if (!$dryRun) {
                DB::table('inventories')->updateOrInsert(
                    ['part_id' => $row->part_id],
                    [
                        'on_hand' => (float) $row->on_hand,
                        'as_of_date' => now()->toDateString(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        $deletedOrphans = 0;
        if ($purgeOrphans) {
            $orphanIds = DB::table('inventories as i')
                ->leftJoin('gci_part_vendor as gpv', 'gpv.id', '=', 'i.part_id')
                ->whereNull('gpv.id')
                ->pluck('i.id');

            $deletedOrphans = $orphanIds->count();
            if ($deletedOrphans > 0 && !$dryRun) {
                DB::table('inventories')->whereIn('id', $orphanIds->all())->delete();
            }
        }

        $this->line("inventories: upserted={$upserts}, deleted_orphans={$deletedOrphans}");

        if (DB::getSchemaBuilder()->hasTable('gci_inventories')) {
            $gciSums = DB::table('location_inventory')
                ->select('gci_part_id', DB::raw('SUM(qty_on_hand) as on_hand'))
                ->whereNotNull('gci_part_id')
                ->groupBy('gci_part_id')
                ->get();

            $gciUpserts = 0;
            foreach ($gciSums as $row) {
                $gciUpserts++;
                if (!$dryRun) {
                    DB::table('gci_inventories')->updateOrInsert(
                        ['gci_part_id' => $row->gci_part_id],
                        [
                            'on_hand' => (float) $row->on_hand,
                            'as_of_date' => now()->toDateString(),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }

            $this->line("gci_inventories: upserted={$gciUpserts}");
        }
    }
}
