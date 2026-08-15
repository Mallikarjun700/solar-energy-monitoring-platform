<?php

namespace Database\Seeders;

use App\Enums\DeadLetterStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeadLetterEventSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('dead_letter_events')->insert([
            [
                'event_id' => 'telemetry:site-1:001',
                'device_id' => 1,
                'original_payload' => json_encode([
                    'site_id' => 1,
                    'panel_voltage' => 420,
                    'current' => 15.5,
                    'timestamp' => now()->toDateTimeString(),
                ]),
                'error_type' => 'TimeoutException',
                'failure_reason' => 'Connection timeout while pushing telemetry to upstream service.',
                'attempt_count' => 1,
                'first_failed_at' => now()->subMinutes(20),
                'last_failed_at' => now()->subMinutes(15),
                'status' => DeadLetterStatus::PENDING->value,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'event_id' => 'telemetry:site-2:002',
                'device_id' => 2,
                'original_payload' => json_encode([
                    'site_id' => 2,
                    'battery_level' => 12,
                    'status' => 'critical',
                ]),
                'error_type' => 'HttpRequestException',
                'failure_reason' => 'The queue worker failed to process telemetry payload.',
                'attempt_count' => 3,
                'first_failed_at' => now()->subHours(2),
                'last_failed_at' => now()->subMinutes(10),
                'status' => DeadLetterStatus::FAILED->value,
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subMinutes(10),
            ],
            [
                'event_id' => 'battery:alert:010',
                'device_id' => 10,
                'original_payload' => json_encode([
                    'battery_id' => 10,
                    'level' => 18,
                    'state' => 'critical',
                ]),
                'error_type' => 'RuntimeException',
                'failure_reason' => 'Battery threshold alert could not be dispatched.',
                'attempt_count' => 2,
                'first_failed_at' => now()->subHours(5),
                'last_failed_at' => now()->subMinutes(30),
                'status' => DeadLetterStatus::INVESTIGATING->value,
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subMinutes(30),
            ],
        ]);
    }
}