<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ──────────────────────────────────────────────
        // INCOMING ARRIVALS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('incoming_arrivals')) {
            Schema::create('incoming_arrivals', function (Blueprint $table) {
                $table->id();
                $table->string('arrival_no')->unique();
                $table->string('transaction_no')->nullable()->unique(); // SOxxxxx
                $table->string('invoice_no')->nullable();
                $table->date('invoice_date')->nullable();

                $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
                $table->foreignId('trucking_company_id')->nullable(); // FK added later
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

                // Vessel / shipment
                $table->string('vessel')->nullable();
                $table->date('etd')->nullable();
                $table->date('eta')->nullable();
                $table->date('eta_gci')->nullable();

                // Document references
                $table->string('bill_of_lading')->nullable();
                $table->string('pen_no')->nullable();
                $table->date('pen_date')->nullable();
                $table->string('aju_no')->nullable();

                // Files
                $table->string('bill_of_lading_file')->nullable();
                $table->string('delivery_note_file')->nullable();
                $table->string('invoice_file')->nullable();
                $table->string('packing_list_file')->nullable();

                // Commercial
                $table->string('price_term')->nullable(); // FOB, CIF, etc.
                $table->string('hs_code')->nullable();
                $table->string('port_of_loading')->nullable();
                $table->string('country')->nullable();
                $table->string('currency', 10)->nullable();

                $table->text('notes')->nullable();

                // Status
                $table->string('status')->default('pending'); // pending | completed | cancelled

                // References (FK added later to avoid ordering issues)
                $table->unsignedBigInteger('purchase_order_id')->nullable();

                $table->softDeletes();
                $table->timestamps();

                $table->index('arrival_no');
                $table->index('invoice_no');
                $table->index('vendor_id');
                $table->index('status');
                $table->index('eta');
            });
        }

        // ──────────────────────────────────────────────
        // INCOMING ARRIVAL ITEMS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('incoming_arrival_items')) {
            Schema::create('incoming_arrival_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('arrival_id')->constrained('incoming_arrivals')->cascadeOnDelete();
                $table->foreignId('gci_part_id')->nullable()->constrained('gci_parts')->nullOnDelete();

                // Material details
                $table->string('material_group')->nullable();
                $table->string('size')->nullable();

                // Quantity
                $table->decimal('qty_goods', 20, 4)->default(0);
                $table->string('unit_goods', 20)->nullable();
                $table->decimal('qty_bundle', 20, 4)->nullable();
                $table->string('unit_bundle', 20)->nullable();

                // Weight
                $table->decimal('weight_nett', 20, 4)->nullable();
                $table->string('unit_weight', 20)->nullable();
                $table->decimal('weight_gross', 20, 4)->nullable();

                // Pricing
                $table->decimal('price', 20, 4)->nullable();
                $table->decimal('total_price', 20, 4)->nullable();

                $table->text('notes')->nullable();

                // FOC flag
                $table->boolean('is_foc')->default(false);

                $table->softDeletes();
                $table->timestamps();

                $table->index('arrival_id');
                $table->index('gci_part_id');
            });
        }

        // ──────────────────────────────────────────────
        // INCOMING ARRIVAL CONTAINERS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('incoming_arrival_containers')) {
            Schema::create('incoming_arrival_containers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('arrival_id')->constrained('incoming_arrivals')->cascadeOnDelete();
                $table->string('container_no');
                $table->string('seal_code')->nullable();
                $table->string('size')->nullable(); // 20ft, 40ft, etc.

                $table->unique(['arrival_id', 'container_no']);

                $table->softDeletes();
                $table->timestamps();

                $table->index('container_no');
            });
        }

        // ──────────────────────────────────────────────
        // INCOMING ARRIVAL CONTAINER INSPECTIONS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('incoming_arrival_container_inspections')) {
            Schema::create('incoming_arrival_container_inspections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('arrival_container_id')->unique('ac_insp_container_id_unique')
                    ->nullable();
                $table->foreign('arrival_container_id', 'ac_insp_container_id_fk')
                    ->references('id')->on('incoming_arrival_containers')
                    ->cascadeOnDelete();

                // Inspection result
                $table->string('status')->default('ok'); // ok | damage
                $table->string('seal_condition')->nullable(); // ok | broken | missing
                $table->string('container_condition')->nullable(); // ok | dented | hole | etc.

                // Photos
                $table->string('photo_front')->nullable();
                $table->string('photo_back')->nullable();
                $table->string('photo_left')->nullable();
                $table->string('photo_right')->nullable();
                $table->string('photo_inside')->nullable();
                $table->string('photo_seal')->nullable();

                $table->text('notes')->nullable();

                $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('inspected_at')->nullable();

                $table->softDeletes();
                $table->timestamps();
            });
        }

        // ──────────────────────────────────────────────
        // INCOMING RECEIVES
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('incoming_receives')) {
            Schema::create('incoming_receives', function (Blueprint $table) {
                $table->id();
                $table->foreignId('arrival_item_id')->constrained('incoming_arrival_items')->cascadeOnDelete();
                $table->foreignId('gci_part_id')->nullable()->constrained('gci_parts')->nullOnDelete();

                // Receive details
                $table->string('tag')->nullable(); // batch / lot number
                $table->decimal('qty', 20, 4)->default(0);
                $table->string('qty_unit', 20)->nullable();

                // Bundle
                $table->decimal('bundle_qty', 20, 4)->nullable();
                $table->string('bundle_unit', 20)->nullable();

                // Weight
                $table->decimal('weight', 20, 4)->nullable();
                $table->decimal('net_weight', 20, 4)->nullable();
                $table->decimal('gross_weight', 20, 4)->nullable();
                $table->decimal('weight_kgm', 20, 4)->nullable();

                // Location
                $table->string('location_code')->nullable();

                // Quality
                $table->string('qc_status')->nullable(); // ok | reject | pending

                // Reference
                $table->string('jo_po_number')->nullable();

                // Actual receipt date
                $table->dateTime('ata_date')->nullable();

                // QC audit
                $table->timestamp('qc_audited_at')->nullable();
                $table->foreignId('qc_audited_by')->nullable()->constrained('users')->nullOnDelete();

                $table->softDeletes();
                $table->timestamps();

                $table->index('arrival_item_id');
                $table->index('gci_part_id');
                $table->index('location_code');
                $table->index('tag');
                $table->index('ata_date');
            });
        }

        // ──────────────────────────────────────────────
        // INCOMING ARRIVAL INSPECTIONS (LEGACY PER-INVOICE, kept for backcompat)
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('incoming_arrival_inspections')) {
            Schema::create('incoming_arrival_inspections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('arrival_id')->unique()->constrained('incoming_arrivals')->cascadeOnDelete();

                $table->string('status')->default('ok'); // ok | damage
                $table->text('notes')->nullable();

                $table->json('issues_left')->nullable();
                $table->json('issues_right')->nullable();
                $table->json('issues_front')->nullable();
                $table->json('issues_back')->nullable();

                $table->string('photo_left')->nullable();
                $table->string('photo_right')->nullable();
                $table->string('photo_front')->nullable();
                $table->string('photo_back')->nullable();
                $table->string('photo_inside')->nullable();

                $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('inspected_at')->nullable();

                $table->softDeletes();
                $table->timestamps();
            });
        }

        // ──────────────────────────────────────────────
        // INCOMING ARRIVAL INSPECTION ISSUES (Normalized)
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('incoming_arrival_inspection_issues')) {
            Schema::create('incoming_arrival_inspection_issues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('arrival_inspection_id')->constrained('incoming_arrival_inspections')->cascadeOnDelete();

                $table->string('position'); // left, right, front, back, inside
                $table->string('issue_type'); // dent, scratch, hole, rust, etc.
                $table->text('description')->nullable();
                $table->string('photo')->nullable();

                $table->softDeletes();
                $table->timestamps();

                $table->index('arrival_inspection_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_arrival_inspection_issues');
        Schema::dropIfExists('incoming_arrival_inspections');
        Schema::dropIfExists('incoming_receives');
        Schema::dropIfExists('incoming_arrival_container_inspections');
        Schema::dropIfExists('incoming_arrival_containers');
        Schema::dropIfExists('incoming_arrival_items');
        Schema::dropIfExists('incoming_arrivals');
    }
};