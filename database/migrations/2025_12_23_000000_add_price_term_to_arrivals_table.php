<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            $table->string('price_term', 50)
                ->nullable()
                ->after('bill_of_lading')
                ->comment('Incoterms / price term, e.g. FOB, CIF, EXW');
        });
    }

    public function down(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            $table->dropColumn('price_term');
        });
    }
};

