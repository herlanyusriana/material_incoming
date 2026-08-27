<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ──────────────────────────────────────────────
        // PRODUCTION ORDERS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('production_orders')) {
            Schema::create('production_orders', function (Blueprint $table) {
                $table->id();
                $table->string('production_order_number')->unique();
                $table->string('transaction_no')->nullable()->unique(); // WOxxxxx

                // Part being produced
                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

                // Planning
                $table->date('plan_date');
                $table->decimal('qty_planned', 20, 4);
                $table->decimal('qty_actual', 20, 4)->default(0);

                // Machine
                $table->foreignId('machine_id')->nullable()->constrained('machines')->nullOnDelete();

                // Status
                $table->string('status')->default('planned'); // planned | material_hold | in_progress | completed | cancelled

                // Workflow
                $table->string('workflow_stage')->nullable();
                $table->dateTime('start_time')->nullable();
                $table->dateTime('end_time')->nullable();

                // Dates
                $table->date('material_requested_at')->nullable();
                $table->date('material_issued_at')->nullable();
                $table->date('material_handed_over_at')->nullable();
                $table->date('fg_supplied_to_wh_at')->nullable();
                $table->date('fg_handed_over_to_wh_at')->nullable();
                $table->date('last_handover_at')->nullable();

                // Users
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('material_requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('material_issued_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('material_handed_over_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('fg_supplied_to_wh_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('fg_handed_over_to_wh_by')->nullable()->constrained('users')->nullOnDelete();

                // Kanban
                $table->boolean('is_kanban_released')->default(false);
                $table->timestamp('kanban_released_at')->nullable();

                // Active operator lock
                $table->timestamp('active_operator_started_at')->nullable();
                $table->string('active_operator_username')->nullable();

                // References (FK constraints added in 000007 to avoid circular deps)
                $table->unsignedBigInteger('mps_id')->nullable();
                $table->unsignedBigInteger('mrp_run_id')->nullable();
                $table->unsignedBigInteger('planning_line_id')->nullable();
                $table->unsignedBigInteger('daily_plan_cell_id')->nullable();

                $table->softDeletes();
                $table->timestamps();

                $table->index('production_order_number');
                $table->index('gci_part_id');
                $table->index('status');
                $table->index('plan_date');
                $table->index('machine_id');
            });
        }

        // ──────────────────────────────────────────────
        // PRODUCTION ORDER RESERVED MATERIALS (Normalized)
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('production_order_reserved_materials')) {
            Schema::create('production_order_reserved_materials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

                $table->decimal('qty_reserved', 20, 4);
                $table->decimal('qty_consumed', 20, 4)->default(0);
                $table->string('batch_no')->nullable();
                $table->string('location_code')->nullable();

                $table->timestamp('reserved_at')->nullable();
                $table->timestamp('consumed_at')->nullable();

                $table->foreignId('reserved_by')->nullable()->constrained('users')->nullOnDelete();

                $table->softDeletes();
                $table->timestamps();

                $table->index('production_order_id');
                $table->index('gci_part_id');
                $table->index('batch_no');
            });
        }

        // ──────────────────────────────────────────────
        // PRODUCTION ORDER MATERIAL REQUESTS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('production_order_material_requests')) {
            Schema::create('production_order_material_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();

                $table->string('request_no')->unique();
                $table->date('request_date');
                $table->string('status')->default('draft'); // draft | requested | issued | partial | cancelled

                $table->text('notes')->nullable();

                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();

                $table->softDeletes();
                $table->timestamps();

                $table->index('production_order_id');
                $table->index('request_no');
                $table->index('status');
            });
        }

        // ──────────────────────────────────────────────
        // PRODUCTION ORDER MATERIAL REQUEST ITEMS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('production_order_material_request_items')) {
            Schema::create('production_order_material_request_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('material_request_id');
                $table->foreign('material_request_id', 'pomri_material_request_fk')
                    ->references('id')->on('production_order_material_requests')
                    ->cascadeOnDelete();
                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

                $table->decimal('qty_requested', 20, 4);
                $table->decimal('qty_issued', 20, 4)->default(0);
                $table->string('unit', 20)->nullable();

                $table->string('location_code')->nullable();
                $table->string('batch_no')->nullable();

                $table->text('notes')->nullable();

                $table->softDeletes();
                $table->timestamps();

                $table->index('material_request_id', 'pomri_material_request_idx');
                $table->index('gci_part_id');
            });
        }

        // ──────────────────────────────────────────────
        // PRODUCTION ORDER MATERIAL ISSUES
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('production_order_material_issues')) {
            Schema::create('production_order_material_issues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
                $table->foreignId('material_request_id')->nullable()->constrained('production_order_material_requests')->nullOnDelete();

                $table->string('issue_no')->unique();
                $table->date('issue_date');

                $table->text('notes')->nullable();

                $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();

                $table->softDeletes();
                $table->timestamps();

                $table->index('production_order_id');
                $table->index('issue_no');
            });
        }

        // ──────────────────────────────────────────────
        // PRODUCTION ORDER MATERIAL ISSUE ITEMS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('production_order_material_issue_items')) {
            Schema::create('production_order_material_issue_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('material_issue_id')->constrained('production_order_material_issues')->cascadeOnDelete();
                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

                $table->decimal('qty_issued', 20, 4);
                $table->string('unit', 20)->nullable();

                $table->string('location_code')->nullable();
                $table->string('batch_no')->nullable();

                $table->softDeletes();
                $table->timestamps();

                $table->index('material_issue_id');
                $table->index('gci_part_id');
            });
        }

        // ──────────────────────────────────────────────
        // PRODUCTION HOURLY REPORTS (renamed from production_gci_hourly_reports)
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('production_hourly_reports')) {
            Schema::create('production_hourly_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_order_id')->nullable()->constrained('production_orders')->nullOnDelete();

                // Work order reference (legacy, FK added in 000007)
                $table->unsignedBigInteger('work_order_id')->nullable();

                $table->foreignId('machine_id')->nullable()->constrained('machines')->nullOnDelete();

                // Report data
                $table->string('time_range')->nullable();
                $table->decimal('target', 20, 4)->nullable();
                $table->decimal('actual', 20, 4)->nullable();

                // NG tracking
                $table->decimal('ng', 20, 4)->default(0);
                $table->string('ng_reason')->nullable();
                $table->decimal('ng_scrap', 20, 4)->default(0);
                $table->decimal('ng_rework', 20, 4)->default(0);
                $table->decimal('ng_hold', 20, 4)->default(0);

                // Output
                $table->string('output_type')->nullable();
                $table->string('process_name')->nullable();
                $table->string('output_part_no')->nullable();
                $table->string('output_part_name')->nullable();

                // Operator
                $table->string('operator_name')->nullable();
                $table->string('shift')->nullable();

                // Offline reference (downtime id — FK added in 000007)
                $table->unsignedBigInteger('offline_id')->nullable();

                // Machine name snapshot (for backward compatibility)
                $table->string('machine_name')->nullable();

                // Split NG
                $table->decimal('ng_split', 20, 4)->default(0);

                // WIP
                $table->decimal('wip_start', 20, 4)->nullable();
                $table->decimal('wip_end', 20, 4)->nullable();

                // Actual machine
                $table->foreignId('actual_machine_id')->nullable()->constrained('machines')->nullOnDelete();

                $table->softDeletes();
                $table->timestamps();

                $table->index('production_order_id');
                $table->index('work_order_id');
                $table->index('machine_id');
                $table->index('time_range');
                $table->index('operator_name');
            });
        }

        // ──────────────────────────────────────────────
        // PRODUCTION DOWNTIMES (unified)
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('production_downtimes')) {
            Schema::create('production_downtimes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_order_id')->nullable()->constrained('production_orders')->nullOnDelete();

                // Work order reference (legacy, FK added in 000007)
                $table->unsignedBigInteger('work_order_id')->nullable();

                $table->foreignId('machine_id')->nullable()->constrained('machines')->nullOnDelete();

                // Downtime details
                $table->string('offline_id')->nullable();
                $table->string('reason_category')->nullable();
                $table->string('reason_code')->nullable();
                $table->text('description')->nullable();

                $table->dateTime('start_time');
                $table->dateTime('end_time')->nullable();
                $table->integer('duration_minutes')->nullable();

                // Operator
                $table->string('operator_name')->nullable();

                // Shift
                $table->string('shift')->nullable();

                // Refill
                $table->string('refill_by')->nullable();
                $table->timestamp('refilled_at')->nullable();

                // Machine name snapshot
                $table->string('machine_name')->nullable();

                $table->softDeletes();
                $table->timestamps();

                $table->index('production_order_id');
                $table->index('work_order_id');
                $table->index('machine_id');
                $table->index('start_time');
                $table->index('reason_category');
            });
        }

        // ──────────────────────────────────────────────
        // PRODUCTION WORK ORDERS (renamed from production_gci_work_orders)
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('production_work_orders')) {
            Schema::create('production_work_orders', function (Blueprint $table) {
                $table->id();
                $table->string('work_order_no')->unique();
                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

                $table->decimal('qty_target', 20, 4);
                $table->decimal('qty_actual', 20, 4)->default(0);

                $table->string('status')->default('pending'); // pending | in_progress | completed | cancelled

                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();

                $table->softDeletes();
                $table->timestamps();

                $table->index('work_order_no');
                $table->index('gci_part_id');
                $table->index('status');
            });
        }

        // ──────────────────────────────────────────────
        // PRODUCTION MATERIAL LOTS (renamed from production_gci_material_lots)
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('production_material_lots')) {
            Schema::create('production_material_lots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_order_id')->nullable()->constrained('production_orders')->nullOnDelete();
                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();

                $table->string('lot_no')->nullable();
                $table->string('batch_no')->nullable();
                $table->decimal('qty', 20, 4);
                $table->string('unit', 20)->nullable();

                $table->string('location_code')->nullable();
                $table->string('status')->default('available'); // available | consumed | returned

                $table->timestamp('received_at')->nullable();
                $table->timestamp('consumed_at')->nullable();

                $table->softDeletes();
                $table->timestamps();

                $table->index('production_order_id');
                $table->index('gci_part_id');
                $table->index('lot_no');
                $table->index('batch_no');
                $table->index('status');
            });
        }

        // ──────────────────────────────────────────────
        // PRODUCTION INSPECTIONS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('production_inspections')) {
            Schema::create('production_inspections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();

                $table->string('inspection_no')->unique();
                $table->date('inspection_date');

                $table->string('type')->nullable(); // first_piece, in_process, final
                $table->string('result')->default('pending'); // pending | pass | fail | rework

                $table->string('inspector_name')->nullable();
                $table->text('notes')->nullable();

                $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();

                $table->softDeletes();
                $table->timestamps();

                $table->index('production_order_id');
                $table->index('inspection_no');
                $table->index('result');
            });
        }

        // ──────────────────────────────────────────────
        // PRODUCTION ORDER ACTIVITIES (audit log)
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('production_order_activities')) {
            Schema::create('production_order_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();

                $table->string('action'); // created, started, paused, resumed, completed, etc.
                $table->text('description')->nullable();

                $table->json('metadata')->nullable();

                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();

                $table->softDeletes();
                $table->timestamps();

                $table->index('production_order_id');
                $table->index('action');
            });
        }

        // ──────────────────────────────────────────────
        // PRODUCTION ORDER ↔ ARRIVAL (SO ↔ WO traceability)
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('production_order_arrivals')) {
            Schema::create('production_order_arrivals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
                $table->foreignId('arrival_id')->constrained('incoming_arrivals')->cascadeOnDelete();

                $table->unique(['production_order_id', 'arrival_id']);

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_arrivals');
        Schema::dropIfExists('production_order_activities');
        Schema::dropIfExists('production_inspections');
        Schema::dropIfExists('production_material_lots');
        Schema::dropIfExists('production_work_orders');
        Schema::dropIfExists('production_downtimes');
        Schema::dropIfExists('production_hourly_reports');
        Schema::dropIfExists('production_order_material_issue_items');
        Schema::dropIfExists('production_order_material_issues');
        Schema::dropIfExists('production_order_material_request_items');
        Schema::dropIfExists('production_order_material_requests');
        Schema::dropIfExists('production_order_reserved_materials');
        Schema::dropIfExists('production_orders');
    }
};