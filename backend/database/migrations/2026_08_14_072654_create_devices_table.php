<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('device_type');
            $table->string('serial_number')->unique();
            $table->string('status')->default('OFFLINE');
            $table->timestamp('last_seen_at')->nullable();

            $table->timestamps();

            $table->index(['asset_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
