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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();

            $table->uuid('tenant_id')->unique();

            $table->boolean('enabled')->default(true);

            $table->string('channel', 30)->default('log');

            $table->string('email')->nullable();

            $table->text('webhook_url')->nullable();

            $table->text('webhook_secret')->nullable();

            $table->json('severity_levels')->nullable();

            $table->timestamps();

            $table->index(
                ['tenant_id', 'enabled'],
                'notification_preferences_tenant_enabled_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
