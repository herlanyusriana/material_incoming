<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Replace machine_id with machine_name (from BOM) in production_planning_lines
        Schema::table('production_planning_lines', function (Blueprint $table) {
            // Drop old foreign key
            if (Schema::hasColumn('production_planning_lines', 'machine_id')) {
                $table->dropConstrainedForeignId('machine_id');
            }
            // Add machine_name and process_name (sourced from BOM)
            $table->string('machine_name')->nullable()->after('gci_part_id');
            $table->string('process_name')->nullable()->after('machine_name');
        });

        // Replace machine_id with machine_name in production_orders
        if (Schema::hasTable('production_orders')) {
            Schema::table('production_orders', function (Blueprint $table) {
                if (Schema::hasColumn('production_orders', 'machine_id')) {
                    $table->dropConstrainedForeignId('machine_id');
                }
                if (!Schema::hasColumn('production_orders', 'machine_name')) {
                    $table->string('machine_name')->nullable()->after('gci_part_id');
                }
                if (!Schema::hasColumn('production_orders', 'process_name')) {
                    $table->string('process_name')->nullable()->after('machine_name');
                }
            });
        }

        // Drop the production_machines table - machines come from BOM
        Schema::dropIfExists('production_machines');
    }

    public function down(): void
    {
        // Recreate production_machines
        if (!Schema::hasTable('production_machines')) {
            Schema::create('production_machines', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('group_name')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Revert production_planning_lines
        Schema::table('production_planning_lines', function (Blueprint $table) {
            $table->dropColumn(['machine_name', 'process_name']);
            $table->foreignId('machine_id')->nullable()->constrained('production_machines')->onDelete('set null');
        });

        // Revert production_orders
        if (Schema::hasTable('production_orders')) {
            Schema::table('production_orders', function (Blueprint $table) {
                if (Schema::hasColumn('production_orders', 'machine_name')) {
                    $table->dropColumn('machine_name');
                }
                if (Schema::hasColumn('production_orders', 'process_name')) {
                    $table->dropColumn('process_name');
                }
                $table->foreignId('machine_id')->nullable()->constrained('production_machines')->onDelete('set null');
            });
        }
    }
};
