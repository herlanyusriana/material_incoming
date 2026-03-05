<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigratePartsLegacyToGci extends Command
{
    protected $signature = 'parts:migrate-legacy-to-gci
        {--dry-run : Simulate only, no writes}
        {--limit=0 : Limit processed rows (0 = all)}';

    protected $description = 'Migrate missing parts_legacy rows into gci_parts + gci_part_vendor and remap legacy part_id references.';

    /** @var array<string,string> */
    private array $refColumns = [
        'arrival_items' => 'part_id',
        'inventories' => 'part_id',
        'location_inventory' => 'part_id',
        'location_inventory_adjustments' => 'part_id',
        'bin_transfers' => 'part_id',
        'inventory_transfers' => 'part_id',
        'bom_items' => 'incoming_part_id',
        'bom_item_substitutes' => 'incoming_part_id',
        'purchase_order_items' => 'vendor_part_id',
    ];

    public function handle(): int
    {
        if (!Schema::hasTable('parts_legacy')) {
            $this->warn('Table parts_legacy not found. Nothing to migrate.');
            return self::SUCCESS;
        }

        if (!Schema::hasTable('gci_part_vendor') || !Schema::hasTable('gci_parts')) {
            $this->error('Required tables gci_part_vendor/gci_parts are missing.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));

        $q = DB::table('parts_legacy as pl')
            ->leftJoin('gci_part_vendor as gpv', 'gpv.id', '=', 'pl.id')
            ->select('pl.*')
            ->whereNull('gpv.id')
            ->whereNotNull('pl.vendor_id')
            ->orderBy('pl.id');

        if ($limit > 0) {
            $q->limit($limit);
        }

        $rows = $q->get();
        if ($rows->isEmpty()) {
            $this->info('No missing legacy rows to migrate.');
            return self::SUCCESS;
        }

        $this->info('Found missing rows: ' . $rows->count() . ($dryRun ? ' (dry-run)' : ''));

        $stats = [
            'created_gci_parts' => 0,
            'created_vendor_links' => 0,
            'reused_vendor_links' => 0,
            'remapped_refs' => 0,
            'skipped_no_vendor' => 0,
        ];

        DB::transaction(function () use ($rows, $dryRun, &$stats): void {
            foreach ($rows as $row) {
                $oldId = (int) $row->id;
                $vendorId = (int) ($row->vendor_id ?? 0);
                if ($vendorId <= 0) {
                    $stats['skipped_no_vendor']++;
                    continue;
                }

                $gciPartId = $this->resolveOrCreateGciPartId($row, $dryRun, $stats);
                if ($gciPartId <= 0) {
                    continue;
                }

                $existingLink = DB::table('gci_part_vendor')
                    ->where('gci_part_id', $gciPartId)
                    ->where('vendor_id', $vendorId)
                    ->first();

                if ($existingLink) {
                    $newId = (int) $existingLink->id;
                    if ($newId !== $oldId) {
                        $stats['remapped_refs'] += $this->remapReferences($oldId, $newId, $dryRun);
                    }
                    $stats['reused_vendor_links']++;
                    continue;
                }

                $idTaken = DB::table('gci_part_vendor')->where('id', $oldId)->exists();
                $insertId = $idTaken ? null : $oldId;

                $payload = [
                    'gci_part_id' => $gciPartId,
                    'vendor_id' => $vendorId,
                    'vendor_part_no' => strtoupper(trim((string) ($row->part_no ?? ''))),
                    'vendor_part_name' => $row->part_name_vendor,
                    'register_no' => $row->register_no,
                    'price' => (float) ($row->price ?? 0),
                    'uom' => $row->uom,
                    'hs_code' => strtoupper(trim((string) ($row->hs_code ?? ''))),
                    'quality_inspection' => $this->toBool($row->quality_inspection ?? null),
                    'status' => $row->status ?: 'active',
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ];

                $newId = 0;
                if ($dryRun) {
                    $newId = $insertId ?: -1;
                } else {
                    if ($insertId !== null) {
                        $payload['id'] = $insertId;
                        DB::table('gci_part_vendor')->insert($payload);
                        $newId = $insertId;
                    } else {
                        $newId = (int) DB::table('gci_part_vendor')->insertGetId($payload);
                    }
                }

                if ($insertId === null) {
                    // ID collision: remap old references to new vendor-link id.
                    $targetId = $dryRun ? -1 : $newId;
                    if ($targetId > 0) {
                        $stats['remapped_refs'] += $this->remapReferences($oldId, $targetId, $dryRun);
                    }
                }

                $stats['created_vendor_links']++;
            }
        });

        $this->newLine();
        foreach ($stats as $k => $v) {
            $this->line("{$k}: {$v}");
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function resolveOrCreateGciPartId(object $row, bool $dryRun, array &$stats): int
    {
        $existingId = (int) ($row->gci_part_id ?? 0);
        if ($existingId > 0 && DB::table('gci_parts')->where('id', $existingId)->exists()) {
            return $existingId;
        }

        $partNo = strtoupper(trim((string) ($row->part_no ?? '')));
        if ($partNo === '') {
            return 0;
        }

        $match = DB::table('gci_parts')
            ->whereRaw('UPPER(TRIM(part_no)) = ?', [$partNo])
            ->orderBy('id')
            ->first();

        if ($match) {
            return (int) $match->id;
        }

        if ($dryRun) {
            $stats['created_gci_parts']++;
            return -1;
        }

        $newId = (int) DB::table('gci_parts')->insertGetId([
            'part_no' => $partNo,
            'part_name' => trim((string) ($row->part_name_gci ?: $row->part_name_vendor ?: $partNo)),
            'classification' => 'RM',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stats['created_gci_parts']++;
        return $newId;
    }

    private function remapReferences(int $oldId, int $newId, bool $dryRun): int
    {
        $total = 0;
        foreach ($this->refColumns as $table => $column) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }

            $count = (int) DB::table($table)->where($column, $oldId)->count();
            if ($count <= 0) {
                continue;
            }

            if (!$dryRun) {
                DB::table($table)->where($column, $oldId)->update([$column => $newId]);
            }

            $total += $count;
        }

        return $total;
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $v = strtoupper(trim((string) $value));
        return in_array($v, ['1', 'TRUE', 'YES', 'Y'], true);
    }
}
