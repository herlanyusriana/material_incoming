<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('price_type', 50);
            $table->string('currency', 10)->default('IDR');
            $table->string('uom', 20)->nullable();
            $table->decimal('min_qty', 18, 3)->nullable();
            $table->decimal('price', 18, 3)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['gci_part_id', 'price_type', 'status'], 'pricing_part_type_status_idx');
            $table->index(['vendor_id', 'price_type'], 'pricing_vendor_type_idx');
            $table->index(['customer_id', 'price_type'], 'pricing_customer_type_idx');
        });

        if (Schema::hasTable('gci_part_vendor')) {
            $rows = DB::table('gci_part_vendor')
                ->select('gci_part_id', 'vendor_id', 'price', 'uom', 'created_at', 'updated_at')
                ->whereNotNull('gci_part_id')
                ->whereNotNull('vendor_id')
                ->where('price', '>', 0)
                ->get();

            foreach ($rows as $row) {
                DB::table('pricing_masters')->insert([
                    'gci_part_id' => $row->gci_part_id,
                    'vendor_id' => $row->vendor_id,
                    'customer_id' => null,
                    'price_type' => 'purchase_price',
                    'currency' => 'IDR',
                    'uom' => $row->uom,
                    'min_qty' => null,
                    'price' => $row->price,
                    'effective_from' => $row->created_at ? date('Y-m-d', strtotime((string) $row->created_at)) : now()->toDateString(),
                    'effective_to' => null,
                    'status' => 'active',
                    'notes' => 'Migrated from vendor part price',
                    'created_by' => null,
                    'updated_by' => null,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_masters');
    }
};
