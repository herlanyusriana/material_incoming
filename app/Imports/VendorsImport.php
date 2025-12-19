<?php

namespace App\Imports;

use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class VendorsImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading, SkipsEmptyRows
{
    private ?array $existingKeys = null;
    private array $seenKeys = [];

    public function model(array $row)
    {
        $this->loadExistingKeys();

        $vendorName = trim((string) ($row['vendor_name'] ?? ''));
        $countryCode = strtoupper(trim((string) ($row['country_code'] ?? '')));
        $key = $this->makeKey($vendorName, $countryCode);

        if (isset($this->seenKeys[$key])) {
            throw new \RuntimeException("Import dibatalkan: ada data duplikat di file (vendor_name={$vendorName}, country_code={$countryCode}).");
        }
        if (isset($this->existingKeys[$key])) {
            throw new \RuntimeException("Import dibatalkan: data sudah ada di sistem (vendor_name={$vendorName}, country_code={$countryCode}).");
        }

        $this->seenKeys[$key] = true;

        return new Vendor([
            'vendor_name' => $vendorName,
            'country_code' => $countryCode,
            'contact_person' => $row['contact_person'] ?? null,
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'] ?? null,
            'address' => $row['address'] ?? null,
            'bank_account' => $row['bank_account'] ?? null,
            'status' => strtolower($row['status'] ?? 'active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'vendor_name' => 'required|string|max:255',
            'country_code' => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'bank_account' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    private function makeKey(string $vendorName, string $countryCode): string
    {
        $name = preg_replace('/\s+/', ' ', strtolower(trim($vendorName))) ?? '';
        $country = strtolower(trim($countryCode));
        return "{$country}|{$name}";
    }

    private function loadExistingKeys(): void
    {
        if ($this->existingKeys !== null) {
            return;
        }

        $keys = [];
        Vendor::withTrashed()
            ->select(['vendor_name', 'country_code'])
            ->orderBy('id')
            ->chunk(1000, function ($vendors) use (&$keys) {
                foreach ($vendors as $vendor) {
                    $keys[$this->makeKey((string) $vendor->vendor_name, (string) ($vendor->country_code ?? ''))] = true;
                }
            });

        $this->existingKeys = $keys;
    }
}
