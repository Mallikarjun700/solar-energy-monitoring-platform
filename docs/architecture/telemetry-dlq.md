# Telemetry Dead Letter Queue (DLQ)

## 1. Problem

Telemetry events can fail during processing because of temporary infrastructure failures,
invalid payloads, unavailable dependencies, or unexpected processing errors.

Retries handle temporary failures, but some events can still fail after all retry attempts.

Without a DLQ, these events could be lost or repeatedly retried.

---

## 2. Why DLQ Is Required

The DLQ provides a controlled location for telemetry events that cannot be
successfully processed.

It provides:

- Failure isolation
- Failure investigation
- Event preservation
- Manual/automated replay
- Operational monitoring
- Recovery after infrastructure failures

---

## 3. When Events Enter the DLQ

An event enters the DLQ when:

1. Maximum retry attempts are exhausted.
2. The failure is classified as non-retryable.

Example:

Telemetry → Processing → Failure → Retry → Retry → Retry → DLQ

---

## 4. Retry Exhaustion

The retry mechanism attempts transient failures according to the configured
retry policy.

When the maximum retry count is reached, the event is passed to the
DeadLetterService.

The original event and failure information are stored in the DLQ.

---

## 5. Non-Retryable Failures

Examples include:

- Invalid event ID
- Invalid device ID
- Malformed payload
- Missing required fields
- Invalid telemetry values

These failures do not require repeated retries and can be sent directly
to the DLQ.

---

## 6. DLQ Data Structure

The DLQ stores:

- event_id
- device_id
- original_payload
- error_type
- failure_reason
- attempt_count
- first_failed_at
- last_failed_at
- status
- timestamps

The original payload is preserved so that the event can be replayed.

---

## 7. DLQ Lifecycle

pending
   ↓
investigating
   ↓
replayed
   ↓
resolved

If replay fails:

replayed
   ↓
failed

The event remains available for further investigation.

---

## 8. Replay Mechanism

An operator can retrieve DLQ events using:

GET /api/dlq

A specific event can be replayed using:

POST /api/dlq/{id}/replay

Replay sends the original payload through the existing telemetry
processing pipeline.

The replay does not directly insert telemetry data.

---

## 9. Idempotency During Replay

DLQ replay uses the existing telemetry idempotency mechanism.

The event_id remains unchanged during replay.

If the event has already been processed:

event_id exists
     ↓
idempotency check
     ↓
duplicate prevented

This makes replay safe.

---

## 10. Failure Scenarios

### Temporary database failure

Processing fails → retry → retry → retry → DLQ.

After the database is restored:

DLQ → replay → successful processing.

### Invalid telemetry

Invalid event → validation failure → DLQ.

No unnecessary retries are performed.

### Replay failure

DLQ → replay → processing failure → status = failed.

The DLQ record remains available.

---

## 11. Monitoring Considerations

Important operational metrics include:

- DLQ depth
- DLQ arrival rate
- Retry exhaustion rate
- Non-retryable failure rate
- Replay success rate
- Replay failure rate
- Age of oldest DLQ event

A sudden increase in DLQ events can indicate a problem in the
telemetry processing pipeline.

---

## 12. API

### List DLQ events

GET /api/dlq

Optional filters:

GET /api/dlq?status=pending

GET /api/dlq?event_id=evt-123

### Replay DLQ event

POST /api/dlq/{id}/replay

---

## 13. Testing

The DLQ implementation verifies:

- Failed events are captured.
- Retry exhaustion creates DLQ records.
- Non-retryable failures create DLQ records.
- DLQ events can be listed.
- DLQ events can be replayed.
- Failed replays remain recoverable.
- Replay does not create duplicate telemetry.
- Existing telemetry and idempotency tests continue to pass.

---

## 14. Trade-offs

### Advantages

- Prevents silent data loss.
- Provides recovery capability.
- Isolates permanently failed events.
- Improves troubleshooting.
- Supports operational monitoring.
- Works with idempotent processing.

### Disadvantages

- Adds operational complexity.
- Requires additional storage.
- Requires monitoring.
- Replay needs careful handling.
- DLQ records may contain sensitive telemetry data.
- Unresolved DLQ records can grow over time.

---

## 15. Architectural Decision

The telemetry platform uses a DLQ as the final failure-isolation mechanism
after retry exhaustion or non-retryable failure.

Replay always uses the existing telemetry processing pipeline and therefore
retains the platform's idempotency guarantees.