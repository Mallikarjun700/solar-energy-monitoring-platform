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
        Schema::create('dead_letter_events', function (Blueprint $table) {
            $table->id();

            $table->string('event_id')->index();
            $table->unsignedBigInteger('device_id')->nullable()->index();

            $table->json('original_payload');

            $table->string('error_type');
            $table->text('failure_reason')->nullable();

            $table->unsignedInteger('attempt_count')->default(0);

            $table->timestamp('first_failed_at')->nullable();
            $table->timestamp('last_failed_at')->nullable();

            $table->string('status')->default('pending')->index();

            $table->timestamps();

            $table->index(['event_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dead_letter_events');
    }
};
