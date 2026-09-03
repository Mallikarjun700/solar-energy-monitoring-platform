<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();

            $table->string('key', 255)->unique();

            $table->string('request_hash', 64);

            $table->unsignedSmallInteger('status_code')->nullable();

            $table->json('response_body')->nullable();

            $table->string('correlation_id')->nullable();

            $table->timestamp('expires_at')->nullable()->index();

            $table->timestamps();

            $table->index(['request_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
