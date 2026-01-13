<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            if (!Schema::hasColumn('arrivals', 'invoice_file')) {
                $table->string('invoice_file')->nullable()->after('delivery_note_file');
            }
        });
    }

    public function down(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            if (Schema::hasColumn('arrivals', 'invoice_file')) {
                $table->dropColumn('invoice_file');
            }
        });
    }
};

