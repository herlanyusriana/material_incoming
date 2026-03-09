<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Restructure stock_at_customers from 31 day-columns to row-per-date.
 *
 * Before: 1 row = period (YYYY-MM) + customer + part + day_1..day_31
 * After:  1 row = stock_date (DATE) + customer + part + qty
 */
return new class extends Migration {
    public function up(): void
    {
        // 1) Create the new table
        Schema::create('stock_at_customers_new', function (Blueprint $table) {
            $table->id();
            $table->date('stock_date');
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('gci_part_id')->nullable()->constrained('gci_parts')->nullOnDelete();
            $table->string('part_no', 100);
            $table->string('part_name', 255)->nullable();
            $table->string('model', 255)->nullable();
            $table->string('status', 20)->nullable();
            $table->decimal('qty', 20, 3)->default(0);
            $table->timestamps();

            $table->unique(['stock_date', 'customer_id', 'part_no']);
            $table->index(['stock_date', 'customer_id']);
            $table->index(['stock_date', 'gci_part_id']);
            $table->index('gci_part_id');
        });

        // 2) Migrate existing data: expand day columns into individual rows
        $oldRecords = DB::table('stock_at_customers')->get();
        foreach ($oldRecords as $rec) {
            $year = (int) substr($rec->period, 0, 4);
            $month = (int) substr($rec->period, 5, 2);
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $col = 'day_' . $d;
                $qty = (float) ($rec->{$col} ?? 0);
                if ($qty == 0) {
                    continue; // skip zero rows to keep table lean
                }

                $stockDate = sprintf('%04d-%02d-%02d', $year, $month, $d);

                DB::table('stock_at_customers_new')->updateOrInsert(
                    [
                        'stock_date' => $stockDate,
                        'customer_id' => $rec->customer_id,
                        'part_no' => $rec->part_no,
                    ],
                    [
                        'gci_part_id' => $rec->gci_part_id,
                        'part_name' => $rec->part_name,
                        'model' => $rec->model,
                        'status' => $rec->status,
                        'qty' => $qty,
                        'created_at' => $rec->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // 3) Swap tables
        Schema::rename('stock_at_customers', 'stock_at_customers_old');
        Schema::rename('stock_at_customers_new', 'stock_at_customers');
        Schema::dropIfExists('stock_at_customers_old');
    }

    public function down(): void
    {
        // Recreate old structure
        Schema::create('stock_at_customers_old', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7);
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('gci_part_id')->nullable()->constrained('gci_parts')->nullOnDelete();
            $table->string('part_no', 100);
            $table->string('part_name', 255)->nullable();
            $table->string('model', 255)->nullable();
            $table->string('status', 20)->nullable();

            for ($i = 1; $i <= 31; $i++) {
                $table->decimal('day_' . $i, 20, 3)->default(0);
            }

            $table->timestamps();
            $table->unique(['period', 'customer_id', 'part_no']);
            $table->index(['period', 'customer_id']);
            $table->index(['period', 'gci_part_id']);
        });

        // Migrate back: collapse rows into day columns
        $rows = DB::table('stock_at_customers')
            ->orderBy('stock_date')
            ->get();

        foreach ($rows as $row) {
            $date = \Carbon\Carbon::parse($row->stock_date);
            $period = $date->format('Y-m');
            $dayNum = (int) $date->format('j');

            $existing = DB::table('stock_at_customers_old')
                ->where('period', $period)
                ->where('customer_id', $row->customer_id)
                ->where('part_no', $row->part_no)
                ->first();

            if ($existing) {
                DB::table('stock_at_customers_old')
                    ->where('id', $existing->id)
                    ->update(['day_' . $dayNum => $row->qty, 'updated_at' => now()]);
            } else {
                $payload = [
                    'period' => $period,
                    'customer_id' => $row->customer_id,
                    'gci_part_id' => $row->gci_part_id,
                    'part_no' => $row->part_no,
                    'part_name' => $row->part_name,
                    'model' => $row->model,
                    'status' => $row->status,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ];
                $payload['day_' . $dayNum] = $row->qty;
                DB::table('stock_at_customers_old')->insert($payload);
            }
        }

        Schema::rename('stock_at_customers', 'stock_at_customers_date');
        Schema::rename('stock_at_customers_old', 'stock_at_customers');
        Schema::dropIfExists('stock_at_customers_date');
    }
};
