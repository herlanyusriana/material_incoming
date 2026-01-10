<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gci_parts', function (Blueprint $table) {
            if (!Schema::hasColumn('gci_parts', 'classification')) {
                $table->string('classification', 10)->default('FG')->after('part_no');
                $table->index('classification');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gci_parts', function (Blueprint $table) {
            if (Schema::hasColumn('gci_parts', 'classification')) {
                $table->dropIndex(['classification']);
                $table->dropColumn('classification');
            }
        });
    }
};

