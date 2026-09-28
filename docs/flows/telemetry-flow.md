# Telemetry Flow

Telemetry is the primary reliability-sensitive data path in the platform.

## End-to-end flow

```text
Device / Client
      |
      v
POST /api/v1/telemetry/events
      |
      +--> Authentication
      +--> telemetry:write
      +--> Rate limiting
      +--> Request-size validation
      +--> Idempotency
      |
      v
Accept batch
HTTP 202
      |
      v
ProcessTelemetryBatchJob
      |
      v
Database Queue
      |
      v
Queue Worker
      |
      v
TelemetryService
      |
      +--> Validate / transform
      +--> Persist telemetry
      +--> Update related state as implemented
      |
      v
Supabase PostgreSQL
```

## 1. Ingestion

The client submits telemetry to:

`POST /api/v1/telemetry/events`

The endpoint is protected by the `telemetry:write` ability.

The request also passes throttling, size validation, and idempotency middleware.

## 2. Asynchronous acceptance

The API does not perform the complete telemetry processing path synchronously.

Instead, it accepts the batch and dispatches `ProcessTelemetryBatchJob`.

The API returns HTTP 202 to indicate that processing is asynchronous.

## 3. Queue processing

The job is handled by the database-backed queue.

Current documented processing controls:

- up to 250 events per job
- up to 3 attempts
- 60-second job timeout
- 90-second retry-after

A queue worker must be running for processing to continue.

## 4. Persistence

The queue worker invokes the telemetry processing service.

Telemetry is stored in the separate PostgreSQL telemetry database, currently hosted by Supabase.

## 5. Duplicate delivery

Telemetry processing is designed to tolerate duplicate delivery.

Database-level idempotency uses the event identity/unique constraint and an insert-or-ignore style persistence path where applicable.

Therefore:

`same event delivered twice → one logical stored event`

## 6. Failure and retry

A transient processing failure can be retried.

The intended lifecycle is:

```text
Processing failure
      |
      v
Retry with backoff
      |
      +--> success → complete
      |
      +--> attempts exhausted
                    |
                    v
                   DLQ
```

## 7. DLQ replay

After an event reaches the domain DLQ, an authorized operator can inspect it and request replay.

Replay returns the event to the normal processing path.

Because processing remains idempotent, replay is safer than directly inserting the record into the telemetry table.

## 8. Query paths

Processed telemetry can be accessed through:

- paginated event queries
- cursor-based event queries
- latest telemetry for a device
- telemetry health endpoint

## Operational signals

Monitor:

- queue depth
- oldest queued job age
- failed jobs
- DLQ size
- processing latency
- ingestion rate
- database errors
- worker health

## Related documentation

- [Async Processing](../architecture/async-processing-final.md)
- [Queue Worker](../architecture/queue-worker.md)
- [Telemetry DLQ](../architecture/telemetry-dlq.md)
- [API Reference](../api/api-reference.md)
