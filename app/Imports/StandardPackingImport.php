<?php

namespace App\Imports;

use App\Models\StandardPacking;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Collection;
use App\Models\GciPart;
use App\Models\Customer;

class StandardPackingImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private function norm($val) {
        if ($val === null) return null;
        $t = trim((string)$val);
        return $t === '' ? null : $t;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $customerName = $this->norm($row['customer']);
            $partNo = $this->norm($row['part_no'] ?? $row['part_number'] ?? null);
            
            if (!$partNo) continue;

            // 1. Resolve Customer
            $customer = null;
            if ($customerName) {
                $customer = Customer::where('name', $customerName)->first();
            }

            // 2. Resolve Part (Strict by Customer if detected, otherwise simple match if unique)
            $partQuery = GciPart::where('part_no', $partNo);
            if ($customer) {
                $partQuery->where('customer_id', $customer->id);
            }
            $part = $partQuery->first();

            // If not found, try without customer (maybe global part) - Optional logic
            if (!$part && !$customer) {
                // If no customer specified in excel, try find any part with that number
                // But risk of collision. Let's stick to safe logic.
            }

            if (!$part) {
                // Skip if part not found
                continue;
            }

            // 3. Prepare Data
            $delClass = $this->norm($row['del_class'] ?? $row['delivery_class'] ?? 'Main');
            $qty = (float) ($row['packing_qty'] ?? $row['qty'] ?? 0);
            $uom = $this->norm($row['uom'] ?? 'PCS');
            $trolley = $this->norm($row['trolly_type'] ?? $row['trolley_type'] ?? null);

            // 4. Update or Create
            StandardPacking::updateOrCreate(
                [
                    'gci_part_id' => $part->id,
                    'delivery_class' => $delClass
                ],
                [
                    'packing_qty' => $qty,
                    'uom' => $uom,
                    'trolley_type' => $trolley,
                    'status' => 'active'
                ]
            );
        }
    }
}
