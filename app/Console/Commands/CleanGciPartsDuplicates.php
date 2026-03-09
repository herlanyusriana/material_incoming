<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\GciPart;

class CleanGciPartsDuplicates extends Command
{
    protected $signature = 'gci:clean-duplicates {--dry-run : Only show what would be done}';
    protected $description = 'Clean duplicate GCI parts by merging them into one record per part_no';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $duplicates = GciPart::select('part_no')
            ->groupBy('part_no')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('part_no');

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate GCI parts found.');
            return 0;
        }

        $this->info("Found {$duplicates->count()} duplicate part numbers.");

        // Find all tables that have a foreign key pointing to gci_parts
        // Or tables that have column names likely to be part references
        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $allTables = [];
        foreach ($tables as $t) {
            $allTables[] = array_values((array) $t)[0];
        }

        $targetColumns = ['gci_part_id', 'part_id', 'component_part_id', 'wip_part_id'];
        $mappings = [];

        foreach ($allTables as $table) {
            if ($table === 'gci_parts')
                continue;
            $cols = Schema::getColumnListing($table);
            foreach ($targetColumns as $col) {
                if (in_array($col, $cols)) {
                    $mappings[$table][] = $col;
                }
            }
        }

        $this->info("Identified " . count($mappings) . " tables with potential part references.");

        foreach ($duplicates as $partNo) {
            $records = GciPart::where('part_no', $partNo)->orderBy('id')->get();
            $master = $records->first();
            $redundantIds = $records->slice(1)->pluck('id')->toArray();

            $this->warn("\nProcessing Part: {$partNo}");
            $this->line("Master Record: ID {$master->id} | Name: {$master->part_name}");
            $this->line("Redundant IDs: " . implode(', ', $redundantIds));

            if ($dryRun)
                continue;

            DB::transaction(function () use ($master, $redundantIds, $mappings) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');

                // Special handling for pivot tables with unique constraints
                $pivotTables = ['customer_gci_part', 'gci_part_vendor'];
                foreach ($pivotTables as $pivot) {
                    if (Schema::hasTable($pivot)) {
                        $fk = ($pivot === 'customer_gci_part') ? 'gci_part_id' : 'gci_part_id';
                        $otherFk = ($pivot === 'customer_gci_part') ? 'customer_id' : 'vendor_id';

                        foreach ($redundantIds as $oldId) {
                            $links = DB::table($pivot)->where($fk, $oldId)->get();
                            foreach ($links as $link) {
                                $exists = DB::table($pivot)->where($otherFk, $link->$otherFk)->where($fk, $master->id)->exists();
                                if ($exists) {
                                    DB::table($pivot)->where('id', $link->id)->delete();
                                } else {
                                    DB::table($pivot)->where('id', $link->id)->update([$fk => $master->id]);
                                }
                            }
                        }
                    }
                }

                // General update for all other tables/columns
                foreach ($mappings as $table => $cols) {
                    if (in_array($table, $pivotTables))
                        continue;

                    foreach ($cols as $col) {
                        DB::table($table)->whereIn($col, $redundantIds)->update([$col => $master->id]);
                    }
                }

                // Finally delete redundant gci_parts
                DB::table('gci_parts')->whereIn('id', $redundantIds)->delete();

                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            });
        }

        $this->info("\nCleanup completed successfully.");
        return 0;
    }
}
