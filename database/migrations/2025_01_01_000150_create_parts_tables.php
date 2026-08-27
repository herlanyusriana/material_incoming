<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create parts table
        // A lingering `parts` VIEW (created later by replace_parts_table_with_view)
        // is NOT dropped by `migrate:fresh` (which only removes base tables). If
        // left in place it collides with this CREATE TABLE. Drop defensively —
        // only when `parts` is actually a VIEW (never a real table).
        if (\Illuminate\Support\Facades\DB::selectOne(
            "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'parts' AND TABLE_TYPE = 'VIEW'"
        )) {
            \Illuminate\Support\Facades\DB::statement('DROP VIEW IF EXISTS parts');
        }
        if (!Schema::hasTable('parts')) {
            Schema::create('parts', function (Blueprint $table) {
                $table->id();
                $table->string('part_no')->unique();
                $table->string('part_name_gci')->nullable();
                $table->foreignId('vendor_id')->nullable()->constrained('vendors')->onDelete('set null');
                $table->string('status')->default('active');
                $table->string('uom')->nullable();
                $table->decimal('price', 20, 3)->nullable();
                $table->string('hs_code')->nullable();
                $table->boolean('quality_inspection')->default(false);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // Create inventories table
        if (!Schema::hasTable('inventories')) {
            Schema::create('inventories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('part_id')->unique()->constrained('parts')->onDelete('cascade');
                $table->decimal('on_hand', 20, 3)->default(0);
                $table->decimal('on_order', 20, 3)->default(0);
                $table->decimal('allocated', 20, 3)->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('parts');
    }
};
