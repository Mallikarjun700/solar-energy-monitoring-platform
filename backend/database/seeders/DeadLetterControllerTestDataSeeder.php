<?php

namespace Database\Seeders;

use App\Enums\DeadLetterStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeadLetterControllerTestDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('dead_letter_events')->insert([
            [
                'event_id' => 'evt-123',
                'device_id' => 101,
                'original_payload' => json_encode([
                    'event_id' => 'evt-123',
                    'device_id' => 101,
                    'power_kw' => 52.4,
                ]),
                'error_type' => 'PROCESSING_ERROR',
                'failure_reason' => 'Database unavailable',
                'attempt_count' => 3,
                'first_failed_at' => now(),
                'last_failed_at' => now(),
                'status' => DeadLetterStatus::PENDING->value,
            ],
            [
                'event_id' => 'evt-replay-001',
                'device_id' => 101,
                'original_payload' => json_encode([
                    'event_id' => 'evt-replay-001',
                    'device_id' => 101,
                    'power_kw' => 52.4,
                ]),
                'error_type' => 'PROCESSING_ERROR',
                'failure_reason' => 'Temporary database failure',
                'attempt_count' => 3,
                'first_failed_at' => now(),
                'last_failed_at' => now(),
                'status' => DeadLetterStatus::PENDING->value,
            ],
            [
                'event_id' => 'evt-resolved-001',
                'device_id' => 101,
                'original_payload' => json_encode([
                    'event_id' => 'evt-resolved-001',
                    'device_id' => 101,
                ]),
                'error_type' => 'PROCESSING_ERROR',
                'failure_reason' => 'Temporary failure',
                'attempt_count' => 3,
                'first_failed_at' => now(),
                'last_failed_at' => now(),
                'status' => DeadLetterStatus::RESOLVED->value,
            ],
        ]);

        DB::table('telemetry')->insert([
            [
                'device_id' => 109,
                'recorded_at' => now(),
                'temperature' => 35.5,
                'voltage' => 230,
                'current' => 12.8,
                'power' => 2.9,
                'energy_generated' => 0.0,
                'status' => DeadLetterStatus::RESOLVED->value,
            ],
        ]);
    }
}