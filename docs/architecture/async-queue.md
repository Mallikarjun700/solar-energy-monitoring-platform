## Queue Health

Queue health is measured using two primary metrics:

### Queue Depth

Number of pending jobs waiting for processing.

### Queue Age

Age of the oldest pending job.

Queue depth indicates backlog size, while queue age indicates
how long telemetry is waiting before processing.

A temporary increase in queue depth does not necessarily indicate
a problem if workers are draining the queue quickly.

Sustained growth in both queue depth and queue age indicates that
processing capacity is insufficient and additional workers may be
required.

Example operational thresholds:

- Less than 10 seconds: Healthy
- 10–30 seconds: Warning
- Greater than 30 seconds: Critical

These thresholds are initial operational targets and should be
validated through production benchmarking.


## Job Timeout and Failure Isolation

Each ProcessTelemetryBatchJob processes a limited chunk of telemetry
events.

The current configuration is:

- Maximum events per job: 250
- Maximum attempts: 3
- Job timeout: 60 seconds
- Queue retry_after: 90 seconds

A failure in one chunk does not require successful chunks from the
same API request to be reprocessed.

This provides failure isolation at the queue-job level.

The timeout is intentionally lower than retry_after to reduce the
risk of another worker making the same job available while the
original worker is still processing it.

## Graceful Worker Shutdown

Workers should not be forcefully terminated during application
deployments.

Laravel provides the `queue:restart` command to signal running
workers to restart after completing their current job.

Deployment flow:

1. Deploy the new application version.
2. Send a queue restart signal.
3. Existing workers finish their current jobs.
4. Workers exit.
5. The process supervisor starts new workers using the new code.

This minimizes the risk of interrupting telemetry processing during
deployment.

In production, a process supervisor or container orchestration
platform is responsible for maintaining the desired number of worker
processes.

## Queue Monitoring

The telemetry processing pipeline exposes operational metrics for
queue health.

Primary metrics:

- Pending queue depth
- Oldest pending job age
- Failed job count
- Job retry count
- Processing throughput

Queue depth measures backlog size.

Oldest job age measures processing latency.

Both metrics are required because a small queue can still be unhealthy
if jobs are not being processed.

Example:

Queue depth = 20
Oldest job age = 20 minutes

This indicates a processing problem even though the queue contains
relatively few jobs.

## Retry Metrics

Queue lifecycle events are used to observe telemetry processing
attempts without coupling monitoring logic to the telemetry job itself.

The system tracks:

- Job processing
- Attempt number
- Successful completion
- Permanent failure

App\Jobs\FailingTelemetryTestJob::dispatch();

An attempt count of 1 indicates first-attempt processing.

An attempt count greater than 1 indicates that the job required
a retry.

A job reaching its maximum configured attempts indicates a
persistent processing problem and may subsequently enter the
dead-letter workflow.

Queue metrics are implemented as an operational concern separate
from telemetry business logic.

## End-to-End Queue Failure Handling

The queue failure path is validated using a dedicated intentionally
failing test job.

The test job is configured with a maximum of three attempts.

Expected lifecycle:

1. Job is dispatched.
2. First attempt fails.
3. Job is retried.
4. Second attempt fails.
5. Job is retried again.
6. Third attempt fails.
7. Job reaches terminal failure handling.
8. Failed-job information is persisted.

The failure test is isolated from the production telemetry processing
logic so that reliability behavior can be validated without
introducing intentional failures into business logic.

Laravel's failed_jobs storage and the application's domain-level
Dead Letter Queue are treated as separate concepts.