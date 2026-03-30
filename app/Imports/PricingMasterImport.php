<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\GciPart;
use App\Models\PricingMaster;
use App\Models\Vendor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PricingMasterImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public int $rowCount = 0;
    public int $skippedRows = 0;
    public array $failures = [];

    public function __construct(private readonly ?int $userId = null)
    {
    }

    private function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $idx => $row) {
            try {
                $data = is_array($row) ? $row : $row->toArray();

                $partNo = strtoupper((string) $this->normalize($data['part_no'] ?? null));
                $priceType = (string) $this->normalize($data['price_type'] ?? null);
                $currency = strtoupper((string) $this->normalize($data['currency'] ?? null));
                $status = strtolower((string) ($this->normalize($data['status'] ?? 'active') ?? 'active'));
                $price = is_numeric($data['price'] ?? null) ? (float) $data['price'] : null;
                $effectiveFrom = $this->normalize($data['effective_from'] ?? null);

                if ($partNo === '' || $priceType === '' || $currency === '' || $price === null || !$effectiveFrom) {
                    $this->skippedRows++;
                    continue;
                }

                if (!array_key_exists($priceType, PricingMaster::PRICE_TYPES)) {
                    $this->failures[] = 'Row ' . ($idx + 2) . ": price_type [{$priceType}] tidak valid.";
                    continue;
                }

                if (!in_array($status, ['active', 'inactive'], true)) {
                    $this->failures[] = 'Row ' . ($idx + 2) . ": status [{$status}] tidak valid.";
                    continue;
                }

                $part = GciPart::query()->where('part_no', $partNo)->first();
                if (!$part) {
                    $this->failures[] = 'Row ' . ($idx + 2) . ": part_no [{$partNo}] tidak ditemukan.";
                    continue;
                }

                $vendor = null;
                $vendorName = $this->normalize($data['vendor_name'] ?? null);
                if ($vendorName) {
                    $vendor = Vendor::query()->whereRaw('LOWER(vendor_name) = ?', [strtolower($vendorName)])->first();
                    if (!$vendor) {
                        $this->failures[] = 'Row ' . ($idx + 2) . ": vendor [{$vendorName}] tidak ditemukan.";
                        continue;
                    }
                }

                $customer = null;
                $customerName = $this->normalize($data['customer_name'] ?? null);
                if ($customerName) {
                    $customer = Customer::query()->whereRaw('LOWER(name) = ?', [strtolower($customerName)])->first();
                    if (!$customer) {
                        $this->failures[] = 'Row ' . ($idx + 2) . ": customer [{$customerName}] tidak ditemukan.";
                        continue;
                    }
                }

                PricingMaster::query()->updateOrCreate(
                    [
                        'gci_part_id' => $part->id,
                        'price_type' => $priceType,
                        'vendor_id' => $vendor?->id,
                        'customer_id' => $customer?->id,
                        'effective_from' => $effectiveFrom,
                    ],
                    [
                        'currency' => $currency,
                        'uom' => strtoupper((string) ($this->normalize($data['uom'] ?? null) ?? '')),
                        'min_qty' => is_numeric($data['min_qty'] ?? null) ? (float) $data['min_qty'] : null,
                        'price' => $price,
                        'effective_to' => $this->normalize($data['effective_to'] ?? null),
                        'status' => $status,
                        'notes' => $this->normalize($data['notes'] ?? null),
                        'updated_by' => $this->userId,
                        'created_by' => $this->userId,
                    ]
                );

                $this->rowCount++;
            } catch (\Throwable $e) {
                $this->failures[] = 'Row ' . ($idx + 2) . ': ' . $e->getMessage();
            }
        }
    }
}
