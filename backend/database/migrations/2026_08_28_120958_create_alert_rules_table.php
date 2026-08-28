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
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();

            $table->uuid('tenant_id')->index();

            $table->string('name', 150);

            $table->string('metric', 100)->index();

            $table->string('operator', 30);

            $table->decimal('threshold', 15, 4);

            $table->string('severity', 20)->index();

            $table->string('alert_type', 100)->index();

            $table->boolean('enabled')->default(true)->index();

            $table->timestamps();

            $table->index(
                ['tenant_id', 'enabled'],
                'alert_rules_tenant_enabled_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
