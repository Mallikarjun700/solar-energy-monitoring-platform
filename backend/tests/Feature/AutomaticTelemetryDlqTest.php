<?php

namespace Tests\Feature;

use App\Jobs\ProcessTelemetryBatchJob;
use App\Models\DeadLetterEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class AutomaticTelemetryDlqTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_telemetry_job_is_captured_in_dlq(): void
    {
        $eventId = (string) Str::uuid();

        $events = [
            [
                'event_id' => $eventId,
                'tenant_id' => (string) Str::uuid(),
                'source_id' => (string) Str::uuid(),
                'event_type' => 'telemetry.power',
                'timestamp' => now()->toISOString(),
                'schema_version' => 1,
                'attributes' => [
                    'device_id' => 1,
                ],
                'payload' => [
                    'power_kw' => 52.5,
                ],
            ],
        ];

        $job = new ProcessTelemetryBatchJob($events);

        $exception = new \RuntimeException(
            'Intentional telemetry processing failure.'
        );

        /*
         * Build a realistic queued-job payload containing
         * the serialized ProcessTelemetryBatchJob.
         */
        $queuePayload = [
            'uuid' => (string) Str::uuid(),
            'displayName' => ProcessTelemetryBatchJob::class,
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'maxTries' => 3,
            'maxExceptions' => null,
            'backoff' => null,
            'timeout' => 60,
            'retryUntil' => null,
            'data' => [
                'commandName' => ProcessTelemetryBatchJob::class,
                'command' => serialize($job),
                'batchId' => null,
            ],
        ];

        /*
         * We need an actual Laravel Job instance for JobFailed.
         *
         * The queue fake is not used here because we are testing
         * the terminal failure listener directly.
         */
        Queue::fake();

        $queuedJob = app('queue')->connection('sync');

        $this->assertNotNull($queuedJob);

        /*
         * Instead of depending on an actual worker, test the
         * JobFailed listener through the application event system.
         */
        event(new JobFailed(
            'sync',
            new class($queuePayload) {
                public function __construct(
                    private array $payload
                ) {
                }

                public function resolveName(): string
                {
                    return ProcessTelemetryBatchJob::class;
                }

                public function getJobId(): string
                {
                    return $this->payload['uuid'];
                }

                public function getQueue(): string
                {
                    return 'default';
                }

                public function attempts(): int
                {
                    return 3;
                }

                public function payload(): array
                {
                    return $this->payload;
                }
            },
            $exception
        ));

        $this->assertDatabaseHas('dead_letter_events', [
            'event_id' => $eventId,
            'attempt_count' => 3,
            'status' => 'pending',
        ]);

        $this->assertSame(
            1,
            DeadLetterEvent::where('event_id', $eventId)->count()
        );
    }
}
