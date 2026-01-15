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
        if (Schema::hasColumn('customer_pos', 'customer_part_no')) {
            Schema::table('customer_pos', function (Blueprint $table) {
                // Drop index if exists - check first
                $conn = Schema::getConnection();
                $db = $conn->getDatabaseName();
                $table_name = $conn->getTablePrefix() . 'customer_pos';
                $index_name = 'customer_pos_customer_part_no_index';
                
                $exists = count(DB::select("
                    SELECT * FROM information_schema.statistics 
                    WHERE table_schema = ? 
                    AND table_name = ? 
                    AND index_name = ?
                ", [$db, $table_name, $index_name])) > 0;

                if ($exists) {
                    $table->dropIndex($index_name);
                }
                
                $table->dropColumn('customer_part_no');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_pos', function (Blueprint $table) {
            $table->string('customer_part_no', 100)->nullable();
            $table->index('customer_part_no');
        });
    }
};
