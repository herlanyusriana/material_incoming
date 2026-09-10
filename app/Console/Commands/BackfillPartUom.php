<?php

namespace App\Console\Commands;

use App\Support\Uom;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill gci_parts.uom dari data yang sudah ada:
 *   RM  <- bom_items.consumption_uom (komponen)
 *   WIP/FG <- bom_items.wip_uom
 *   Sisa FG/WIP -> PCE (default unit hasil produksi)
 *   Sisa RM  -> dibiarkan NULL + dilaporkan (tidak menebak).
 *
 * Konflik (part dipakai dengan >1 UOM di BOM) dilaporkan, tidak dipilih.
 */
class BackfillPartUom extends Command
{
    protected $signature = 'uom:backfill {--dry : tampilkan saja tanpa menyimpan}';

    protected $description = 'Backfill UOM stok material (gci_parts.uom) dari BOM + vendor_parts';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');

        $targets = DB::table('gci_parts')
            ->where(function ($q) {
                $q->whereNull('uom')->orWhere('uom', '');
            })
            ->get(['id', 'part_no', 'classification']);

        $filled = ['RM' => 0, 'WIP' => 0, 'FG' => 0, 'defaulted' => 0];
        $conflicts = [];
        $majorityResolved = [];
        $unresolvedRm = [];
        $updates = [];

        foreach ($targets as $part) {
            $candidates = [];

            if ($part->classification === 'RM') {
                $counts = DB::table('bom_items')
                    ->where('component_part_id', $part->id)
                    ->whereNotNull('component_part_id')
                    ->whereRaw("TRIM(COALESCE(consumption_uom,'')) <> ''")
                    ->pluck('consumption_uom')
                    ->map(fn ($u) => Uom::canonical($u))
                    ->filter()
                    ->countBy()
                    ->all();
            } elseif (in_array($part->classification, ['WIP', 'FG'], true)) {
                $counts = DB::table('bom_items')
                    ->where('wip_part_id', $part->id)
                    ->whereNotNull('wip_part_id')
                    ->whereRaw("TRIM(COALESCE(wip_uom,'')) <> ''")
                    ->pluck('wip_uom')
                    ->map(fn ($u) => Uom::canonical($u))
                    ->filter()
                    ->countBy()
                    ->all();
            } else {
                $counts = [];
            }

            if ($counts !== []) {
                arsort($counts);
                $top = array_key_first($counts);
                $topCount = $counts[$top];
                $distinct = count($counts);
                $updates[$part->id] = $top;
                $filled[$part->classification === 'RM' ? 'RM' : ($part->classification === 'WIP' ? 'WIP' : 'FG')]++;
                if ($distinct > 1) {
                    $majorityResolved[] = [$part->part_no, $part->classification, $top, $topCount, array_sum($counts)];
                }
            } elseif ($part->classification === 'FG' || $part->classification === 'WIP') {
                $updates[$part->id] = 'PCE';
                $filled['defaulted']++;
            } else {
                $unresolvedRm[] = $part->part_no;
            }
        }

        if (! $dry && $updates !== []) {
            foreach (array_chunk($updates, 500, true) as $chunk) {
                $case = [];
                $ids = array_keys($chunk);
                foreach ($chunk as $id => $uom) {
                    $case[] = "WHEN {$id} THEN '{$uom}'";
                }
                DB::statement('UPDATE gci_parts SET uom = CASE id ' . implode(' ', $case) . ' END WHERE id IN (' . implode(',', $ids) . ')');
            }
        }

        $this->info('Tanpa UOM awal: ' . count($targets));
        $this->info("  Terisi dari BOM component (RM) : {$filled['RM']}");
        $this->info("  Terisi dari BOM wip (WIP/FG)   : {$filled['WIP']}/{$filled['FG']}");
        $this->info("  Default PCE (FG/WIP tanpa BOM) : {$filled['defaulted']}");
        $this->info('  RM tanpa sumber (tetap NULL)   : ' . count($unresolvedRm));

        if ($majorityResolved !== []) {
            $this->warn("\nKonflik diselesaikan mayoritas (pilih yang paling banyak dipakai di BOM):");
            foreach ($majorityResolved as [$no, $cls, $chosen, $votes, $total]) {
                $this->line("  [{$cls}] {$no}: {$chosen} ({$votes}/{$total} baris BOM)");
            }
        }

        if ($unresolvedRm !== []) {
            $this->warn("\nRM tanpa sumber UOM (set manual di Master Part):");
            $this->line('  ' . implode(', ', array_slice($unresolvedRm, 0, 30)) . (count($unresolvedRm) > 30 ? ' … +' . (count($unresolvedRm) - 30) : ''));
        }

        $after = DB::table('gci_parts')->where(function ($q) {
            $q->whereNull('uom')->orWhere('uom', '');
        })->count();
        $this->info($dry ? "\nDRY RUN — belum tersimpan. Sisa tanpa UOM (simulasi): {$after}" : "\nSelesai. Sisa tanpa UOM: {$after}");

        return self::SUCCESS;
    }
}
