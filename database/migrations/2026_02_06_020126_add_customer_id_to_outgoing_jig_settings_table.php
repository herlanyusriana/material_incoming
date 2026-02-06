<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('outgoing_jig_settings', function (Blueprint $table) {
            // Drop old unique index referencing project_name
            $table->dropUnique(['line', 'project_name']);

            // Drop column
            $table->dropColumn('project_name');

            // Add customer_id
            $table->foreignId('customer_id')->after('line')->constrained('customers')->cascadeOnDelete();

            // Add new unique index
            $table->unique(['line', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_jig_settings', function (Blueprint $table) {
            $table->dropUnique(['line', 'customer_id']);
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');

            $table->string('project_name')->index();
            $table->unique(['line', 'project_name']);
        });
    }
};
