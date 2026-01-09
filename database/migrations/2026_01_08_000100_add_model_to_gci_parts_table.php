<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gci_parts', function (Blueprint $table) {
            if (!Schema::hasColumn('gci_parts', 'model')) {
                $table->string('model', 255)->nullable()->after('part_name');
                $table->index('model');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gci_parts', function (Blueprint $table) {
            if (Schema::hasColumn('gci_parts', 'model')) {
                $table->dropIndex(['model']);
                $table->dropColumn('model');
            }
        });
    }
};

