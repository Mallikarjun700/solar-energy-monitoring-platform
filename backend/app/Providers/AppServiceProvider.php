<?php

namespace App\Providers;

use App\Events\AlertCreated;
use App\Jobs\ProcessTelemetryBatchJob;
use App\Listeners\HandleAlertCreated;
use App\Services\DeadLetterService;
use App\Services\ProductionConfigurationValidator;
use App\Services\QueueMetricsService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(QueueMetricsService::class, fn () => new QueueMetricsService);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        app(ProductionConfigurationValidator::class)->validate();

        RateLimiter::for('telemetry', function ($request) {
            $limit = (int) config('telemetry.rate_limit.requests_per_minute', 60);

            $user = $request->user();

            $key = $user ? 'user:'.$user->getAuthIdentifier() : 'ip:'.$request->ip();

            return Limit::perMinute($limit)->by($key);
        });

        $metricsService = app(QueueMetricsService::class);

        Event::listen(AlertCreated::class, HandleAlertCreated::class);

        Event::listen(JobProcessing::class, function (JobProcessing $event) use ($metricsService) {
            $jobId = $event->job->getJobId();

            $metricsService->start($jobId);

            logger()->info('Queue job processing', [
                'job_id' => $jobId,
                'job_name' => $event->job->resolveName(),
                'queue' => $event->job->getQueue(),
                'attempts' => $event->job->attempts(),
                'correlation_id' => app()->bound('correlation_id') ? app('correlation_id') : null,
            ]);
        });

        Event::listen(JobProcessed::class, function (JobProcessed $event) use ($metricsService) {
            $jobId = $event->job->getJobId();
            $duration = $metricsService->finish($jobId);

            logger()->info('Queue job processed', [
                'job_id' => $jobId,
                'job_name' => $event->job->resolveName(),
                'queue' => $event->job->getQueue(),
                'attempts' => $event->job->attempts(),
                'correlation_id' => app()->bound('correlation_id') ? app('correlation_id') : null,
                'duration_ms' => $duration,
            ]);
        });

        Event::listen(JobFailed::class, function (JobFailed $event) use ($metricsService) {
            $jobId = $event->job->getJobId();
            $duration = $metricsService->finish($jobId);

            logger()->error('Queue job failed', [
                'job_id' => $jobId,
                'job_name' => $event->job->resolveName(),
                'queue' => $event->job->getQueue(),
                'attempts' => $event->job->attempts(),
                'correlation_id' => app()->bound('correlation_id') ? app('correlation_id') : null,
                'duration_ms' => $duration,
                'exception' => $event->exception->getMessage(),
            ]);

            if ($event->job->resolveName() !== ProcessTelemetryBatchJob::class) {
                return;
            }

            try {
                $payload = $event->job->payload();

                $command = $payload['data']['command'] ?? null;

                if ($command === null) {
                    return;
                }

                $job = unserialize($command);

                if (! $job instanceof ProcessTelemetryBatchJob) {
                    return;
                }

                $deadLetterService = app(DeadLetterService::class);

                foreach ($job->events as $telemetryEvent) {
                    $deadLetterService->captureFailedEvent(
                        $telemetryEvent['event_id'],
                        $telemetryEvent['attributes']['device_id'] ?? null,
                        $telemetryEvent,
                        $event->exception,
                        $event->job->attempts()
                    );
                }
            } catch (\Throwable $exception) {
                logger()->error('Failed to capture telemetry job in DLQ', [
                    'exception' => $exception->getMessage(),
                ]);
            }
        });
    }
}
