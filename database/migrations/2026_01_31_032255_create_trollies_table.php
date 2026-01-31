<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trollies', function (Blueprint $row) {
            $row->id();
            $row->string('code')->unique();
            $row->string('type')->nullable(); // external, internal, etc
            $row->string('kind')->nullable(); // backplate, etc (Category)
            $row->text('qr_payload')->nullable();
            $row->string('status')->default('ACTIVE');
            $row->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trollies');
    }
};
