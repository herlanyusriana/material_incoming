<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('parts') || !Schema::hasColumn('parts', 'part_no')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            Schema::table('parts', function (Blueprint $table) {
                $table->unique('part_no', 'parts_part_no_unique');
            });
            return;
        }

        DB::statement('UPDATE parts SET part_no = UPPER(TRIM(part_no)) WHERE part_no IS NOT NULL');

        // Resolve duplicates before enforcing uniqueness.
        $duplicateKeys = DB::table('parts')
            ->select('part_no')
            ->whereNotNull('part_no')
            ->groupBy('part_no')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('part_no');

        if ($duplicateKeys->isNotEmpty()) {
            $hasArrivalItems = Schema::hasTable('arrival_items') && Schema::hasColumn('arrival_items', 'part_id');

            foreach ($duplicateKeys as $partNo) {
                $parts = DB::table('parts')
                    ->where('part_no', $partNo)
                    ->orderBy('id')
                    ->get(['id', 'vendor_id', 'register_no', 'part_name_vendor', 'part_name_gci', 'hs_code', 'status']);

                if ($parts->count() <= 1) {
                    continue;
                }

                $keeper = $parts->first();
                $keeperId = (int) $keeper->id;

                $vendorIds = $parts->pluck('vendor_id')->filter()->unique()->values();
                $canMerge = $vendorIds->count() <= 1;

                foreach ($parts->slice(1) as $dup) {
                    $dupId = (int) $dup->id;

                    if ($canMerge) {
                        if ($hasArrivalItems) {
                            DB::table('arrival_items')->where('part_id', $dupId)->update(['part_id' => $keeperId]);
                        }

                        // Merge missing text fields into keeper (only fills blanks).
                        $updates = [];
                        foreach (['register_no', 'part_name_vendor', 'part_name_gci', 'hs_code'] as $col) {
                            $keeperVal = trim((string) ($keeper->{$col} ?? ''));
                            $dupVal = trim((string) ($dup->{$col} ?? ''));
                            if ($keeperVal === '' && $dupVal !== '') {
                                $updates[$col] = $dupVal;
                                $keeper->{$col} = $dupVal;
                            }
                        }

                        if (($keeper->status ?? '') !== 'active' && ($dup->status ?? '') === 'active') {
                            $updates['status'] = 'active';
                            $keeper->status = 'active';
                        }

                        if (!empty($updates)) {
                            DB::table('parts')->where('id', $keeperId)->update($updates);
                        }

                        DB::table('parts')->where('id', $dupId)->delete();
                    } else {
                        // Different vendor_id for the same part_no: keep records but make part_no unique.
                        $suffix = '-DUP' . $dupId;
                        $newPartNo = substr((string) $partNo, 0, 255 - strlen($suffix)) . $suffix;
                        DB::table('parts')->where('id', $dupId)->update(['part_no' => $newPartNo]);
                    }
                }
            }
        }

        $stillDuplicates = DB::table('parts')
            ->select('part_no', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('part_no')
            ->groupBy('part_no')
            ->having('cnt', '>', 1)
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        if ($stillDuplicates->isNotEmpty()) {
            $summary = $stillDuplicates
                ->map(fn ($row) => "{$row->part_no} ({$row->cnt}x)")
                ->implode(', ');
            throw new RuntimeException("Cannot add unique index on parts.part_no; duplicates remain: {$summary}");
        }

        Schema::table('parts', function (Blueprint $table) {
            $table->unique('part_no', 'parts_part_no_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('parts')) {
            return;
        }

        Schema::table('parts', function (Blueprint $table) {
            $table->dropUnique('parts_part_no_unique');
        });
    }
};
