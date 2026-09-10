<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * UOM package:
     * 1. gci_parts.uom — UOM stok material per part (sumber: vendor_parts.uom).
     *    subcount_uom tetap untuk kebutuhan subcon (count unit), tidak digabung.
     * 2. Backfill + normalisasi PCS->PCE, KG->KGM.
     * 3. Lokasi virtual RECEIVING — stok QC-pass menunggu putaway.
     * 4. Backfill lokasi virtual di receive yang sudah punya stok tapi belum putaway.
     */
    public function up(): void
    {
        Schema::table('gci_parts', function (Blueprint $table) {
            $table->string('uom', 20)->nullable()->after('subcount_process_type');
        });

        // Normalisasi dulu supaya backfill & validasi konsisten.
        DB::statement("UPDATE bom_items SET consumption_uom = 'PCE' WHERE UPPER(TRIM(consumption_uom)) = 'PCS'");
        DB::statement("UPDATE bom_items SET consumption_uom = 'KGM' WHERE UPPER(TRIM(consumption_uom)) IN ('KG','KGS')");
        DB::statement("UPDATE bom_items SET wip_uom = 'PCE' WHERE UPPER(TRIM(wip_uom)) = 'PCS'");
        DB::statement("UPDATE vendor_parts SET uom = 'PCE' WHERE UPPER(TRIM(uom)) = 'PCS'");
        DB::statement("UPDATE vendor_parts SET uom = 'KGM' WHERE UPPER(TRIM(uom)) IN ('KG','KGS')");
        DB::statement("UPDATE inventory_location_stock SET uom = 'PCE' WHERE UPPER(TRIM(uom)) = 'PCS'");

        // Backfill gci_parts.uom dari vendor_parts.uom (part dominan = UOM material part).
        DB::statement(<<<'SQL'
        UPDATE gci_parts gp
        JOIN (
            SELECT gci_part_id,
                   SUBSTRING_INDEX(
                       GROUP_CONCAT(uom ORDER BY cnt DESC SEPARATOR ','),
                   ',', 1) AS uom
            FROM (
                SELECT gci_part_id, TRIM(uom) AS uom, COUNT(*) AS cnt
                FROM vendor_parts
                WHERE TRIM(COALESCE(uom, '')) <> ''
                GROUP BY gci_part_id, TRIM(uom)
            ) t
            GROUP BY gci_part_id
        ) src ON src.gci_part_id = gp.id
        SET gp.uom = src.uom
        SQL);

        // Lokasi virtual RECEIVING untuk stok menunggu putaway.
        DB::table('warehouse_locations')->updateOrInsert(
            ['location_code' => 'RECEIVING'],
            ['class' => 'VIRTUAL', 'zone' => 'INBOUND', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]
        );

        // Receives QC-pass yang sudah terbentuk stok tapi belum putaway → pindahkan
        // baris stoknya ke RECEIVING agar saldo fisik dan tag tetap terkait.
        $receiveTable = 'incoming_receives';
        DB::statement(<<<SQL
        UPDATE inventory_location_stock ils
        JOIN {$receiveTable} r ON r.gci_part_id IS NOT NULL
            AND ils.gci_part_id = (SELECT gci_part_id FROM {$receiveTable} rx WHERE rx.id = r.id)
            AND ils.batch_no = r.tag
            AND UPPER(ils.location_code) <> 'RECEIVING'
        JOIN incoming_arrival_items ai ON ai.id = r.arrival_item_id
        SET ils.location_code = 'RECEIVING'
        SQL);
    }

    public function down(): void
    {
        Schema::table('gci_parts', function (Blueprint $table) {
            $table->dropColumn('uom');
        });
    }
};
