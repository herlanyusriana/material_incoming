<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * WO Material Tracking (Fase A):
     * - production_work_orders: kolom tambahan untuk WO manual + siklus terkunci.
     * - wo_requirements: snapshot kebutuhan per komponen saat WO dibuat
     *   (perubahan BOM berikutnya tidak mengubah WO berjalan).
     * - wo_material_allocations: alokasi stok per TAG (coil) ke WO.
     *   Status: RESERVED -> CONSUMED | RETURNED. "WO keluar = terkunci".
     */
    public function up(): void
    {
        if (! Schema::hasColumn('production_work_orders', 'bom_id')) {
            Schema::table('production_work_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('bom_id')->nullable()->after('gci_part_id');
            });
        }

        Schema::create('wo_requirements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_order_id')->index();
            $table->unsignedBigInteger('gci_part_id')->index();
            $table->unsignedBigInteger('bom_item_id')->nullable();
            $table->string('component_part_no', 100)->nullable();
            $table->decimal('required_qty', 16, 4);
            $table->string('uom', 20)->nullable();
            $table->string('consumption_policy', 30)->default('backflush_return');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('work_order_id')->references('id')->on('production_work_orders')->cascadeOnDelete();
        });

        Schema::create('wo_material_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_order_id')->index();
            $table->unsignedBigInteger('gci_part_id')->index();
            $table->string('tag', 255)->index();
            $table->string('location_code', 50)->nullable();
            $table->decimal('qty_reserved', 16, 4)->default(0);
            $table->decimal('qty_consumed', 16, 4)->default(0);
            $table->decimal('qty_returned', 16, 4)->default(0);
            $table->string('status', 20)->default('RESERVED'); // RESERVED | CONSUMED | RETURNED | CANCELLED
            $table->unsignedBigInteger('receive_id')->nullable(); // incoming_receives.id
            $table->string('movement_ref', 100)->nullable();
            $table->unsignedBigInteger('allocated_by')->nullable();
            $table->unsignedBigInteger('consumed_by')->nullable();
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('work_order_id')->references('id')->on('production_work_orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wo_material_allocations');
        Schema::dropIfExists('wo_requirements');

        Schema::table('production_work_orders', function (Blueprint $table) {
            $table->dropColumn(['bom_id']);
        });
    }
};
