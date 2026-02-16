<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Master data: Machines
        if (!Schema::hasTable('production_machines')) {
            Schema::create('production_machines', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');               // e.g. "PTL Dongshi"
                $table->string('group_name')->nullable(); // e.g. "BACK PLATE / PLATE REAR"
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Production Planning Session (per date)
        if (!Schema::hasTable('production_planning_sessions')) {
            Schema::create('production_planning_sessions', function (Blueprint $table) {
                $table->id();
                $table->date('plan_date')->unique();
                $table->integer('planning_days')->default(7); // number of days to plan ahead
                $table->string('status')->default('draft'); // draft, confirmed, completed
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();
            });
        }

        // Production Planning Lines (rows in the GCI Planning Produksi table)
        if (!Schema::hasTable('production_planning_lines')) {
            Schema::create('production_planning_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('session_id')->constrained('production_planning_sessions')->onDelete('cascade');
                $table->foreignId('machine_id')->nullable()->constrained('production_machines')->onDelete('set null');
                $table->foreignId('gci_part_id')->constrained('gci_parts')->onDelete('cascade');
                $table->decimal('stock_fg_lg', 18, 4)->default(0);       // Stock FG LG
                $table->decimal('stock_fg_gci', 18, 4)->default(0);      // Stock FG GCI
                $table->integer('production_sequence')->nullable();       // Urutan Produksi
                $table->decimal('plan_qty', 18, 4)->default(0);           // Plan GCI qty
                $table->integer('shift')->nullable();                      // Shift 1, 2, or 3
                $table->string('remark')->nullable();                      // LG Plan / GCI Stock
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // Add machine_id to production_orders if not exists
        if (Schema::hasTable('production_orders') && !Schema::hasColumn('production_orders', 'machine_id')) {
            Schema::table('production_orders', function (Blueprint $table) {
                $table->foreignId('machine_id')->nullable()->after('gci_part_id')
                    ->constrained('production_machines')->onDelete('set null');
                $table->foreignId('planning_line_id')->nullable()->after('machine_id')
                    ->constrained('production_planning_lines')->onDelete('set null');
                $table->integer('shift')->nullable()->after('plan_date');
                $table->integer('production_sequence')->nullable()->after('shift');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('production_orders')) {
            Schema::table('production_orders', function (Blueprint $table) {
                if (Schema::hasColumn('production_orders', 'machine_id')) {
                    $table->dropConstrainedForeignId('machine_id');
                }
                if (Schema::hasColumn('production_orders', 'planning_line_id')) {
                    $table->dropConstrainedForeignId('planning_line_id');
                }
                if (Schema::hasColumn('production_orders', 'shift')) {
                    $table->dropColumn('shift');
                }
                if (Schema::hasColumn('production_orders', 'production_sequence')) {
                    $table->dropColumn('production_sequence');
                }
            });
        }

        Schema::dropIfExists('production_planning_lines');
        Schema::dropIfExists('production_planning_sessions');
        Schema::dropIfExists('production_machines');
    }
};
