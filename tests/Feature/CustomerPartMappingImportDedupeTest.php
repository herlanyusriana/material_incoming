<?php

namespace Tests\Feature;

use App\Imports\CustomerPartMappingImport;
use App\Models\Customer;
use App\Models\CustomerPart;
use App\Models\CustomerPartComponent;
use App\Models\GciPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for review finding on Q-001: CustomerPartMappingImport
 * used the legacy App\Models\GciPart whose $fillable lacked customer_id,
 * so ensureGciPart() silently dropped it and the dedupe lookup could never
 * match → re-imports created duplicate gci_parts rows.
 */
class CustomerPartMappingImportDedupeTest extends TestCase
{
    use RefreshDatabase;

    private function row(): array
    {
        return [
            'customer_code' => 'CUST-01',
            'customer_part_no' => 'CP-0001',
            'gci_part_no' => 'GCI-DEDUPE',
            'gci_part_name' => 'Dedupe Part',
            'usage_qty' => 2,
        ];
    }

    public function test_customer_id_is_persisted_via_import(): void
    {
        Customer::create(['code' => 'CUST-01', 'name' => 'Cust Satu', 'status' => 'active']);

        (new CustomerPartMappingImport)->model($this->row());

        $part = GciPart::where('part_no', 'GCI-DEDUPE')->first();
        $this->assertNotNull($part);
        $this->assertNotNull($part->customer_id, 'customer_id harus tersimpan (dulu dibuang oleh fillable model lama)');
    }

    public function test_reimport_does_not_duplicate_gci_part(): void
    {
        Customer::create(['code' => 'CUST-01', 'name' => 'Cust Satu', 'status' => 'active']);

        $import = new CustomerPartMappingImport;
        $import->model($this->row());
        $import->model($this->row()); // file yang sama di-import dua kali

        $this->assertSame(1, GciPart::where('part_no', 'GCI-DEDUPE')->count(), 'Tidak boleh duplikat gci_parts row');
        $this->assertSame(1, CustomerPart::where('customer_part_no', 'CP-0001')->count(), 'CustomerPart tidak boleh duplikat');
        $this->assertSame(1, CustomerPartComponent::count(), 'CustomerPartComponent mapping tidak boleh duplikat');
    }
}