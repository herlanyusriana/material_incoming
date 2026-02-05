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
        Schema::create('outgoing_jig_settings', function (Blueprint $table) {
            $table->id();
            $table->string('line')->index(); // NR-1, NR-2, etc.
            $table->string('project_name')->index(); // Omega6, VT-12, etc.
            $table->integer('uph')->default(0); // Unit Per Hour
            $table->timestamps();

            $table->unique(['line', 'project_name']);
        });

        Schema::create('outgoing_jig_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jig_setting_id')->constrained('outgoing_jig_settings')->cascadeOnDelete();
            $table->date('plan_date')->index();
            $table->integer('jig_qty')->default(0);
            $table->timestamps();

            $table->unique(['jig_setting_id', 'plan_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outgoing_jig_plans');
        Schema::dropIfExists('outgoing_jig_settings');
    }
};
