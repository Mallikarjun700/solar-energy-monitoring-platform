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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('asset_type');
            $table->string('serial_number')->nullable()->unique();
            $table->string('status')->default('ACTIVE');
            $table->string('location')->nullable();

            $table->timestamps();

            $table->index(['plant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
