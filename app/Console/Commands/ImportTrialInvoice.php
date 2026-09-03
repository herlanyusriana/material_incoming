<?php

namespace App\Console\Commands;

use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\Vendor;
use App\Models\NewSchema\Core\VendorPart;
use App\Models\NewSchema\Incoming\IncomingArrival as Arrival;
use App\Models\NewSchema\Incoming\IncomingArrivalItem as ArrivalItem;
use App\Models\NewSchema\Incoming\IncomingReceive as Receive;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Import a TRIAL INVOICE style Excel into the Incoming flow:
 * Arrival -> Items -> Receive -> Stock (InventoryLocationStock).
 *
 * Intended for one-off imports of thin spreadsheets that have no weight /
 * price columns. Missing numeric values are filled with sensible defaults so
 * the rows are not empty (the user explicitly chose "bebas, asal jangan kosong").
 *
 * Expected columns (row 2 header, data from row 3):
 *   invoice | part no | part name | size | vendor | etd | receive date
 *   | tag | qty | unit | issue date | issue qty | stock | prod date
 *   | prod qty | delivery date | delivery qty | invoice date
 */
class ImportTrialInvoice extends Command
{
    protected $signature = 'incoming:import-trail
                            {file : Path to the xlsx file (e.g. excel/TRIAL INVOICE.xlsx)}
                            {--location=A-001 : Stock location_code for putaway}
                            {--date= : Override receive date (Y-m-d), default from sheet or today}';

    protected $description = 'Import a TRIAL INVOICE xlsx into Incoming (Arrival → Receive → Stock)';

    public function handle(): int
    {
        $file = $this->argument('file');
        $path = base_path($file);
        if (!is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $location = strtoupper(trim((string) $this->option('location')));
        if ($location === '') {
            $location = 'A-001';
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $header = null;
        $data = [];
        foreach ($rows as $i => $row) {
            $first = trim((string) ($row[0] ?? ''));
            if ($header === null && stripos($first, 'no') === 0 && stripos((string) ($row[1] ?? ''), 'invoice') === 0) {
                $header = $i;
                continue;
            }
            if ($header !== null && $first !== '' && $first !== 'NO') {
                $data[] = $row;
            }
        }

        if ($header === null || empty($data)) {
            $this->error("No data rows found. Expected a header row with 'invoice' in column B.");
            return self::FAILURE;
        }

        $createdAt = now();
        $userId = \App\Models\NewSchema\Core\User::where('id', '>', 0)->value('id');
        if (!$userId) {
            $userId = null;
        }

        $report = [];
        foreach ($data as $row) {
            $invoiceNo = strtoupper(trim((string) ($row[1] ?? '')));
            $partNo = trim((string) ($row[2] ?? ''));
            $partName = trim((string) ($row[3] ?? ''));
            $size = trim((string) ($row[4] ?? ''));
            $vendorName = trim((string) ($row[5] ?? ''));
            $etd = trim((string) ($row[6] ?? ''));
            $receiveDateRaw = trim((string) ($row[7] ?? ''));
            $tag = trim((string) ($row[8] ?? ''));
            $qty = (int) ($row[9] ?? 0);
            $unit = strtoupper(trim((string) ($row[10] ?? '')));
            $invoiceDateRaw = trim((string) ($row[18] ?? ''));

            if ($invoiceNo === '' || $partNo === '' || $qty <= 0) {
                $report[] = "SKIP (missing invoice/part/qty): " . json_encode($row);
                continue;
            }

            try {
                $result = $this->importRow([
                    'invoice_no' => $invoiceNo,
                    'part_no' => $partNo,
                    'part_name' => $partName,
                    'size' => $size,
                    'vendor_name' => $vendorName,
                    'etd' => $etd,
                    'receive_date' => $receiveDateRaw,
                    'tag' => $tag,
                    'qty' => $qty,
                    'unit' => $unit,
                    'invoice_date' => $invoiceDateRaw,
                    'location' => $location,
                    'user_id' => $userId,
                    'created_at' => $createdAt,
                ]);
                $report[] = "OK: {$result['arrival_no']} part={$partNo} qty={$qty} → stock_gci={$result['gci_part_id']} loc={$location}";
            } catch (\Throwable $e) {
                $report[] = "FAIL: part={$partNo} invoice={$invoiceNo} → " . $e->getMessage();
            }
        }

        foreach ($report as $line) {
            $this->line($line);
        }

        return self::SUCCESS;
    }

    private function importRow(array $d): array
    {
        return DB::transaction(function () use ($d) {
            // Resolve vendor
            $vendor = Vendor::where('vendor_name', 'like', "%{$d['vendor_name']}%")->first();
            if (!$vendor) {
                throw new \RuntimeException("vendor not found: {$d['vendor_name']}");
            }

            // Resolve GCI part
            $gciPart = GciPart::where('part_no', $d['part_no'])->first();
            if (!$gciPart) {
                throw new \RuntimeException("gci part not found: {$d['part_no']}");
            }

            // Ensure VendorPart bridge (vendor_id -> gci_part_id)
            $vendorPart = VendorPart::where('vendor_id', $vendor->id)
                ->where('gci_part_id', $gciPart->id)
                ->first();
            if (!$vendorPart) {
                $vendorPart = VendorPart::create([
                    'gci_part_id' => $gciPart->id,
                    'vendor_id' => $vendor->id,
                    'vendor_part_no' => $d['part_no'],
                    'vendor_part_name' => $d['part_name'] ?: $gciPart->part_name,
                    'status' => 'active',
                    'created_by' => $d['user_id'],
                ]);
            }

            // Parse dates. parseDate handles both "26-Aug" (d-M) and full years.
            $invoiceDate = $this->parseDate($d['invoice_date']) ?: ($this->parseDate($d['receive_date']) ?: today());
            $etd = $this->parseDate($d['etd']);
            $receiveDate = $this->parseDate($d['receive_date']) ?: today();

            // Arrival
            $arrival = Arrival::create([
                'invoice_no' => $d['invoice_no'],
                'invoice_date' => $invoiceDate,
                'vendor_id' => $vendor->id,
                'etd' => $etd,
                'currency' => 'USD',
                'country' => $vendor->vendor_type === 'local' ? 'INDONESIA' : 'SOUTH KOREA',
                'created_by' => $d['user_id'],
                'created_at' => $d['created_at'],
            ]);

            // Item
            $arrivalItem = $arrival->items()->create([
                'vendor_part_id' => $vendorPart->id,
                'gci_part_id' => $gciPart->id,
                'size' => $d['size'] ?: null,
                'qty_goods' => $d['qty'],
                'unit_goods' => $d['unit'] ?: 'SHEET',
                'qty_bundle' => 0,
                'unit_bundle' => null,
                // Sensible non-zero defaults so the row is not empty (0 is "kosong").
                // weight_nett/gross in kg, rough approximation for sheet material.
                'weight_nett' => $this->defaultWeight($d['qty']),
                'unit_weight' => 'KGM',
                'weight_gross' => $this->defaultWeight($d['qty']) * 1.02,
                'total_price' => $this->defaultTotal($d['qty']),
                'price' => $this->defaultPrice($d['qty'], $this->defaultTotal($d['qty'])),
                'created_by' => $d['user_id'],
                'created_at' => $d['created_at'],
            ]);

            // Receive
            $receive = $arrivalItem->receives()->create([
                'tag' => $d['tag'] ?: null,
                'qty' => $d['qty'],
                'qty_unit' => $d['unit'] ?: 'SHEET',
                'bundle_qty' => 0,
                'bundle_unit' => null,
                'weight' => $this->defaultWeight($d['qty']),
                'net_weight' => $this->defaultWeight($d['qty']),
                'gross_weight' => $this->defaultWeight($d['qty']) * 1.02,
                'location_code' => $d['location'],
                'qc_status' => 'pass',
                'ata_date' => Carbon::parse($receiveDate)->setTimeFromTimeString(now()->format('H:i:s')),
                'created_by' => $d['user_id'],
                'created_at' => $d['created_at'],
            ]);

            // Stock putaway (pass)
            $addQty = strtoupper($d['unit'] ?? '') === 'COIL' ? 0.0 : (float) $d['qty'];
            InventoryLocationStock::updateStock(
                $gciPart->id,
                $d['location'],
                $addQty,
                null,
                $receive->tag,
                'RECEIVE',
                null,
                $receive->id,
                $arrival->id,
                $arrival->invoice_no,
                null,
                null,
                $d['user_id']
            );

            return [
                'arrival_no' => $arrival->arrival_no,
                'gci_part_id' => $gciPart->id,
            ];
        });
    }

    /**
     * Rough weight (kg) for a sheet/plate quantity so the row is non-zero.
     * BACK PLATE VT 7 is a steel sheet ~0.25mm x 702 x 1487mm. We use a
     * conservative ~0.4 kg/sheet as a stand-in; user accepted "bebas" defaults.
     */
    private function defaultWeight(int $qty): float
    {
        return round($qty * 0.4, 2);
    }

    /** Rough invoice total (USD) so the row is non-zero. */
    private function defaultTotal(int $qty): float
    {
        return round($qty * 1.2, 2);
    }

    /** Unit price as total/qty (formatted to 3 decimals), non-zero. */
    private function defaultPrice(int $qty, float $total): float
    {
        return $qty > 0 ? round($total / $qty, 3) : 0;
    }

    private function parseDate(?string $raw): ?Carbon
    {
        $raw = trim((string) $raw);
        if ($raw === '' || $raw === '-') {
            return null;
        }

        // setReadDataOnly returns dates as Excel serial numbers (e.g. 46260 = 2026-08-26).
        // Convert those to a Carbon date before trying textual formats.
        if (is_numeric($raw)) {
            $serial = (int) $raw;
            if ($serial > 1000 && $serial < 80000) {
                try {
                    return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($serial));
                } catch (\Throwable) {
                }
            }
        }

        foreach (['d-M-Y', 'd-M-y', 'd-M', 'Y-m-d', 'd/m/Y', 'd M Y', 'd-M-Y', 'M-Y', 'Y-m'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $raw);
            } catch (\Throwable) {
            }
        }

        return null;
    }
}
