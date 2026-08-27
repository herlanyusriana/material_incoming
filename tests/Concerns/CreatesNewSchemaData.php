<?php

namespace Tests\Concerns;

use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\Vendor;
use App\Models\NewSchema\Core\VendorPart;
use App\Models\NewSchema\Incoming\IncomingArrival;
use App\Models\NewSchema\Incoming\IncomingArrivalItem;
use App\Models\NewSchema\Incoming\IncomingReceive;

/**
 * Builds data against the NEW schema (models under App\Models\NewSchema\*),
 * which is what the current controllers/services actually read. The legacy
 * test suite was written against the OLD tables (receives / arrival_items /
 * parts table) and needs to construct data through these models instead.
 */
trait CreatesNewSchemaData
{
    protected function makeNewVendor(string $name = 'Test Vendor'): Vendor
    {
        return Vendor::create([
            'vendor_name' => $name,
            'vendor_type' => 'import',
            'status' => 'active',
        ]);
    }

    protected function makeNewGciPart(string $partNo = 'GCI-001', string $cls = 'RM', ?string $name = null): GciPart
    {
        return GciPart::create([
            'part_no' => $partNo,
            'part_name' => $name ?? $partNo,
            'classification' => $cls,
            'status' => 'active',
        ]);
    }

    protected function makeNewVendorPart(int $vendorId, int $gciPartId, string $vendorPartNo = 'VP-001'): VendorPart
    {
        return VendorPart::create([
            'gci_part_id' => $gciPartId,
            'vendor_id' => $vendorId,
            'vendor_part_no' => $vendorPartNo,
            'vendor_part_name' => $vendorPartNo,
            'quality_inspection' => false,
            'status' => 'active',
        ]);
    }

    protected function makeNewArrival(int $vendorId, string $arrivalNo = 'ARR-NEW-0001'): IncomingArrival
    {
        return IncomingArrival::create([
            'arrival_no' => $arrivalNo,
            'vendor_id' => $vendorId,
            'status' => 'pending',
        ]);
    }

    protected function makeNewArrivalItem(int $arrivalId, int $gciPartId, int $vendorPartId, float $qty = 1, string $unit = 'PCS'): IncomingArrivalItem
    {
        return IncomingArrivalItem::create([
            'arrival_id' => $arrivalId,
            'gci_part_id' => $gciPartId,
            'vendor_part_id' => $vendorPartId,
            'qty_goods' => $qty,
            'unit_goods' => $unit,
            'is_foc' => 0,
        ]);
    }

    protected function makeNewReceive(int $arrivalItemId, string $qcStatus = 'pass', float $qty = 1, string $tag = 'TAG-NEW'): IncomingReceive
    {
        return IncomingReceive::create([
            'arrival_item_id' => $arrivalItemId,
            'tag' => $tag,
            'qty' => $qty,
            'qty_unit' => 'PCS',
            'qc_status' => $qcStatus,
            'ata_date' => now(),
            'location_code' => null,
        ]);
    }

    /**
     * Build the full incoming chain rooted at a Receive (new schema).
     *
     * @return array{0: IncomingReceive, 1: GciPart, 2: VendorPart, 3: IncomingArrivalItem, 4: Vendor, 5: IncomingArrival}
     */
    protected function makeIncomingChain(string $qcStatus = 'pass', string $tag = 'TAG-NEW', float $qty = 1): array
    {
        $vendor = $this->makeNewVendor();
        $gciPart = $this->makeNewGciPart();
        $vendorPart = $this->makeNewVendorPart($vendor->id, $gciPart->id);
        $arrival = $this->makeNewArrival($vendor->id);
        $arrivalItem = $this->makeNewArrivalItem($arrival->id, $gciPart->id, $vendorPart->id);
        $receive = $this->makeNewReceive($arrivalItem->id, $qcStatus, $qty, tag: $tag);

        return [$receive, $gciPart, $vendorPart, $arrivalItem, $vendor, $arrival];
    }
}
