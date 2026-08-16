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
        Schema::create('telemetry_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->uuid('tenant_id');
            $table->uuid('source_id');
            $table->string('event_type', 100);
            $table->timestamp('event_timestamp');
            $table->timestamp('received_at');
            $table->integer('schema_version');
            $table->json('attributes')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event_id']);
            $table->index(['tenant_id']);
            $table->index(['event_timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetry_events');
    }
};
