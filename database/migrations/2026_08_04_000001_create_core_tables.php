<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ──────────────────────────────────────────────
        // USERS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // ──────────────────────────────────────────────
        // ROLES & PERMISSIONS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('display_name');
                $table->text('description')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->string('permission'); // e.g. 'view_arrivals', 'edit_production'
                $table->timestamps();
                $table->unique(['role_id', 'permission']);
            });
        }

        // ──────────────────────────────────────────────
        // UOM (Units of Measure)
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('uoms')) {
            Schema::create('uoms', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->unique(); // KG, PCS, M, etc.
                $table->string('name')->nullable();
                $table->string('category')->nullable(); // weight, length, qty
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // ──────────────────────────────────────────────
        // MACHINES
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('machines')) {
            Schema::create('machines', function (Blueprint $table) {
                $table->id();
                $table->string('machine_code')->unique();
                $table->string('machine_name');
                $table->string('type')->nullable();
                $table->string('capacity')->nullable();
                $table->string('status')->default('active'); // active | maintenance | retired
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // ──────────────────────────────────────────────
        // DEPARTMENTS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // ──────────────────────────────────────────────
        // CUSTOMERS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->string('customer_code')->unique();
                $table->string('customer_name');
                $table->string('country_code')->nullable();
                $table->text('address')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('contact_person')->nullable();
                $table->string('status')->default('active');
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // ──────────────────────────────────────────────
        // VENDORS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('vendors')) {
            Schema::create('vendors', function (Blueprint $table) {
                $table->id();
                $table->string('vendor_code')->unique();
                $table->string('vendor_name');
                $table->string('vendor_type')->nullable(); // local | overseas
                $table->string('country_code')->nullable();
                $table->text('address')->nullable();
                $table->string('bank_account')->nullable();
                $table->string('contact_person')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('signature_path')->nullable();
                $table->string('status')->default('active');
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // ──────────────────────────────────────────────
        // GCI PARTS — Master Part (Internal)
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('gci_parts')) {
            Schema::create('gci_parts', function (Blueprint $table) {
                $table->id();
                $table->string('part_no')->unique();
                $table->string('barcode')->nullable()->unique();
                $table->string('part_name');
                $table->string('size')->nullable();
                $table->string('model')->nullable();
                $table->string('classification')->nullable(); // FG, RM, WIP, etc.
                $table->string('status')->default('active');
                $table->decimal('net_weight', 18, 4)->nullable();
                $table->decimal('gross_weight', 18, 4)->nullable();

                // Consumption policy
                $table->boolean('is_backflush')->default(false);
                $table->string('consumption_policy')->nullable(); // FIFO, LIFO, Manual

                // Subcount
                $table->boolean('subcount_enabled')->default(false);
                $table->string('subcount_uom')->nullable();
                $table->string('subcount_process_type')->nullable();

                // Default location
                $table->string('default_location')->nullable();

                // Policy confirmation
                $table->timestamp('policy_confirmed_at')->nullable();
                $table->foreignId('policy_confirmed_by')->nullable()->constrained('users')->nullOnDelete();

                $table->softDeletes();
                $table->timestamps();

                $table->index('part_no');
                $table->index('classification');
            });
        }

        // ──────────────────────────────────────────────
        // VENDOR PARTS — Pivot Vendor ↔ GciPart
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('vendor_parts')) {
            Schema::create('vendor_parts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();

                // Vendor's part identification
                $table->string('vendor_part_no');
                $table->string('vendor_part_name')->nullable();
                $table->string('register_no')->nullable();

                // Commercial
                $table->decimal('price', 20, 4)->nullable();
                $table->string('currency', 10)->nullable();
                $table->string('uom')->nullable(); // PCS, KG, etc.

                // Quality
                $table->boolean('quality_inspection')->default(false);
                $table->string('status')->default('active');

                // Unique: one vendor can supply one gci_part only once
                $table->unique(['gci_part_id', 'vendor_id']);

                $table->softDeletes();
                $table->timestamps();
            });
        }

        // ──────────────────────────────────────────────
        // CUSTOMER PARTS (used by outgoing domain)
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('customer_parts')) {
            Schema::create('customer_parts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->string('part_no');
                $table->string('part_name')->nullable();
                $table->string('description')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->unique(['customer_id', 'part_no']);
                $table->index('part_no');
            });
        }

        // ──────────────────────────────────────────────
        // WAREHOUSE LOCATIONS
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('warehouse_locations')) {
            Schema::create('warehouse_locations', function (Blueprint $table) {
                $table->id();
                $table->string('location_code')->unique();
                $table->string('location_name')->nullable();
                $table->string('zone')->nullable();
                $table->string('rack')->nullable();
                $table->string('shelf')->nullable();
                $table->string('bin')->nullable();
                $table->string('location_type')->nullable(); // storage, staging, WIP, etc.
                $table->boolean('is_active')->default(true);
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
        Schema::dropIfExists('vendor_parts');
        Schema::dropIfExists('gci_parts');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('machines');
        Schema::dropIfExists('uoms');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
    }
};