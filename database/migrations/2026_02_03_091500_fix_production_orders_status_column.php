<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change status column from ENUM to VARCHAR to support all status values
        DB::statement("ALTER TABLE production_orders MODIFY COLUMN status VARCHAR(255) NOT NULL DEFAULT 'draft'");
        DB::statement("ALTER TABLE production_orders MODIFY COLUMN workflow_stage VARCHAR(255) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal - keep as varchar
    }
};
