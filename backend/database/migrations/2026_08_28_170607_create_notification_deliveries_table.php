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
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('alert_id')
                ->constrained('alerts')
                ->cascadeOnDelete();

            $table->string('channel', 30);

            $table->string('status', 30)
                ->default('pending');

            $table->unsignedInteger('attempts')
                ->default(0);

            $table->timestampTz('first_attempted_at')
                ->nullable();

            $table->timestampTz('last_attempted_at')
                ->nullable();

            $table->timestampTz('delivered_at')
                ->nullable();

            $table->text('last_error')
                ->nullable();

            $table->timestamps();

            $table->unique(
                ['alert_id', 'channel'],
                'notification_deliveries_alert_channel_unique'
            );

            $table->index(
                ['status', 'last_attempted_at'],
                'notification_deliveries_status_attempt_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
