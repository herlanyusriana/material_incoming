<?php

namespace Tests\Concerns;

use App\Models\GciPart;
use App\Models\GciPartVendor;
use App\Models\Part;
use App\Models\Vendor;

trait CreatesTestParts
{
    /**
     * Create a "part" that materialises as a readable row in the `parts` VIEW.
     *
     * `parts` is a read-only view over `gci_part_vendor JOIN gci_parts`, so a
     * part cannot be INSERTed directly. Instead we create a GciPart + Vendor +
     * GciPartVendor, then re-read through the view so the returned `Part` has
     * the view attributes tests used to rely on (id, part_no, vendor_id, ...).
     */
    protected function makePart(string $partNo = 'RM-001', string $status = 'active'): Part
    {
        $vendorId = Vendor::create([
            'vendor_name' => 'Test Vendor',
            'vendor_type' => 'import',
            'status' => 'active',
        ])->id;

        $gciPartId = GciPart::create([
            'part_no' => $partNo,
            'part_name' => $partNo,
            'classification' => 'RM',
            'status' => 'active',
        ])->id;

        $gpv = GciPartVendor::create([
            'gci_part_id' => $gciPartId,
            'vendor_id' => $vendorId,
            'vendor_part_no' => $partNo,
            'vendor_part_name' => $partNo,
            'status' => $status,
        ]);

        return Part::where('id', $gpv->id)->first();
    }
}
