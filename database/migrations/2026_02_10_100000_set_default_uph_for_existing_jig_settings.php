<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Set default UPH per line for existing records
        DB::table('outgoing_jig_settings')
            ->where('line', 'NR1')
            ->where('uph', 0)
            ->update(['uph' => 10]);

        DB::table('outgoing_jig_settings')
            ->where('line', 'NR2')
            ->where('uph', 0)
            ->update(['uph' => 9]);
    }

    public function down(): void
    {
        // No rollback needed - UPH can be manually adjusted
    }
};
