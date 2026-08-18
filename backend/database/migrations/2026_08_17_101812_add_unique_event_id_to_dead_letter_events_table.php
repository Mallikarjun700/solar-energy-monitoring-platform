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
        Schema::table('dead_letter_events', function (Blueprint $table) {
            Schema::table('dead_letter_events', function (Blueprint $table) {
                $table->unique('event_id');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dead_letter_events', function (Blueprint $table) {
            $table->dropUnique('event_id');
        });
    }
};
