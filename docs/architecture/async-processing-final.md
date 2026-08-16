## Queue Processing Model

Telemetry ingestion uses an asynchronous batch-processing model.

The API validates the incoming request and dispatches a
ProcessTelemetryBatchJob to the Laravel database queue.

The API returns HTTP 202 Accepted without waiting for telemetry
persistence to complete.

A queue worker consumes ProcessTelemetryBatchJob and invokes
TelemetryService::ingest().

## Processing Flow

Client
  ↓
Telemetry API
  ↓
ProcessTelemetryBatchJob
  ↓
Database Queue
  ↓
Queue Worker
  ↓
TelemetryService
  ↓
TelemetryEvent
  ↓
Database

## Duplicate Delivery

Queue processing does not rely on exactly-once delivery.

If the same batch is delivered more than once, the existing
database-level idempotency mechanism prevents duplicate event records.

TelemetryService::ingest() uses insertOrIgnore() when persisting
telemetry events.

## API Semantics

The ingestion API returns:

HTTP 202 Accepted

This means the telemetry batch has been accepted for asynchronous
processing but does not necessarily mean that persistence has already
completed.