<?php

namespace App\Console\Commands;

use App\Services\MasterDataWorkbookImporter;
use Illuminate\Console\Command;

class ImportMasterDataWorkbook extends Command
{
    protected $signature = 'master-data:import
                            {path : Absolute path to the master data .xlsx workbook}
                            {--dry-run : Validate and count without writing anything}';

    protected $description = 'Import master FG / master mtrl / BOM sheets from the master data workbook into the ERP schema';

    public function handle(MasterDataWorkbookImporter $importer): int
    {
        $path = $this->argument('path');
        $dryRun = (bool) $this->option('dry-run');

        if (!is_file($path) || !is_readable($path)) {
            $this->error("Workbook tidak ditemukan atau tidak bisa dibaca: {$path}");

            return self::FAILURE;
        }

        try {
            $result = $importer->import($path, $dryRun);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Import gagal: ' . $e->getMessage());

            return self::FAILURE;
        }

        $mode = $dryRun ? 'DRY RUN (tidak ada data ditulis)' : 'IMPORT SELESAI';

        $this->info("=== {$mode} ===");
        $this->table(
            ['Entitas', 'Jumlah'],
            collect($result)->except('warnings')
                ->map(fn ($value, $key) => [$key, (string) $value])
                ->values()
                ->all()
        );

        $warnings = $result['warnings'] ?? [];
        if ($warnings !== []) {
            $this->warn('Warnings (' . count($warnings) . '):');
            foreach ($warnings as $warning) {
                $this->line("  - {$warning}");
            }
        } else {
            $this->line('Warnings: tidak ada.');
        }

        return self::SUCCESS;
    }
}
