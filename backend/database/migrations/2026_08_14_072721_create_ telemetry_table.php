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
        Schema::create('telemetry', function (Blueprint $table) {
            $table->id();

            $table->foreignId('device_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamp('recorded_at');

            $table->decimal('temperature', 10, 2)->nullable();
            $table->decimal('voltage', 10, 2)->nullable();
            $table->decimal('current', 10, 2)->nullable();
            $table->decimal('power', 12, 2)->nullable();
            $table->decimal('energy_generated', 15, 4)->nullable();

            $table->string('status')->nullable();

            $table->index(['device_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetry');
    }
};
