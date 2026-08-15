# ADR-00X: Telemetry Dead Letter Queue (DLQ)

## Context

The telemetry platform processes events received from renewable-energy devices.

The system already supports:

* Telemetry ingestion
* Telemetry processing
* Idempotency using `event_id`
* Retry handling
* Retry backoff

However, some telemetry events may still fail after all permitted retries, while other events may fail because their data is permanently invalid.

If these events are simply discarded, the system loses the ability to:

* Investigate the failure
* Understand why processing failed
* Correct the underlying issue
* Replay the event
* Recover telemetry without asking the device to resend it

Therefore, the system requires a **Dead Letter Queue (DLQ)** mechanism for isolating events that cannot be successfully processed through the normal pipeline.

---

# Why DLQ Is Required

The DLQ provides a controlled destination for telemetry events that cannot be processed successfully.

Without a DLQ:

```text
Telemetry
   ↓
Processing
   ↓
Failure
   ↓
Retries
   ↓
Failure
   ↓
Event Lost
```

With a DLQ:

```text
Telemetry
   ↓
Processing
   ↓
Failure
   ↓
Retries
   ↓
Failure
   ↓
DLQ
   ↓
Investigation / Replay
```

The DLQ therefore acts as a **failure isolation and recovery mechanism**.

It prevents permanently failed events from continuously blocking the normal telemetry processing pipeline.

---

# When Events Enter the DLQ

An event can enter the DLQ in two primary situations.

## 1. Retry Exhaustion

The event has failed the maximum configured number of attempts.

Example:

```text
Attempt 1 → Failure
Attempt 2 → Failure
Attempt 3 → Failure
                  ↓
                 DLQ
```

The retry mechanism stops attempting the event after the configured maximum.

---

## 2. Non-Retryable Failure

Some failures should not be retried.

Examples:

```text
Invalid event_id
Invalid device_id
Malformed payload
Missing required field
Invalid telemetry value
Unsupported event format
```

The event can be sent directly to the DLQ:

```text
Telemetry
   ↓
Validation
   ↓
Permanent Failure
   ↓
DLQ
```

This avoids wasting processing resources on events that cannot succeed without changing the data.

---

# Retry Exhaustion

The current retry policy allows a maximum of **3 attempts**.

Example:

```text
Initial attempt
      ↓
    Failure
      ↓
Retry #1
      ↓
    Failure
      ↓
Retry #2
      ↓
    Failure
      ↓
Retry #3
      ↓
    Failure
      ↓
    DLQ
```

The DLQ therefore becomes the final destination after the normal retry mechanism has been exhausted.

The event should retain information about how many attempts were made.

Example:

```text
attempt_count = 3
status = dead_lettered
```

This information is useful for troubleshooting and operational analysis.

---

# Non-Retryable Failures

Some failures indicate that the event itself is invalid rather than that the infrastructure is temporarily unavailable.

Examples include:

### Missing required information

```text
event_id missing
device_id missing
timestamp missing
```

### Invalid data

```text
Invalid timestamp
Invalid numeric value
Invalid telemetry measurement
```

### Unknown device

```text
device_id does not exist
```

### Invalid payload

```text
Malformed JSON
Unsupported event format
```

These events should not consume retry attempts unnecessarily.

Instead:

```text
Invalid Event
     ↓
Validation
     ↓
Non-Retryable Failure
     ↓
DLQ
```

---

# DLQ Data Structure

The DLQ should preserve enough information to understand and replay the failed event.

A conceptual structure is:

```text
dead_letter_events
---------------------------
id
event_id
device_id
original_payload
error_type
failure_reason
attempt_count
first_failed_at
last_failed_at
status
created_at
updated_at
```

### `event_id`

Identifies the original telemetry event.

### `device_id`

Identifies the device that generated the telemetry.

### `original_payload`

Preserves the original event data so that the event can potentially be replayed.

### `error_type`

Classifies the failure.

Examples:

```text
VALIDATION_ERROR
DATABASE_ERROR
TIMEOUT
DEPENDENCY_ERROR
PROCESSING_ERROR
```

### `failure_reason`

Contains information explaining why processing failed.

### `attempt_count`

Records how many processing attempts occurred.

### `first_failed_at`

Records when the event first entered failure processing.

### `last_failed_at`

Records the most recent failure.

### `status`

Tracks the DLQ event lifecycle.

---

# Replay Mechanism

One of the most important purposes of the DLQ is to support **replay**.

Suppose an event reaches the DLQ:

```text
Event A
   ↓
Processing failure
   ↓
Retries exhausted
   ↓
DLQ
```

An operator investigates the problem and fixes the underlying issue.

The event can then be replayed:

```text
DLQ
 ↓
Replay
 ↓
Telemetry Processing
 ↓
Success
```

The replay should send the original event back through the normal processing pipeline rather than bypassing the existing validation and processing logic.

Conceptually:

```text
DLQ Event
    │
    ▼
Replay Request
    │
    ▼
Normal Telemetry Processing
    │
    ├── Validation
    ├── Idempotency
    ├── Processing
    └── Storage
```

This is important because replay should follow the same rules as normal telemetry ingestion.

---

# Interaction With Idempotency

DLQ replay must work together with the existing idempotency mechanism.

Suppose:

```text
event_id = evt_123
```

The event was originally processed successfully but the worker failed before acknowledging the message.

The event may subsequently appear in the DLQ or be redelivered.

When replayed:

```text
event_id = evt_123
```

The idempotency mechanism ensures that the system does not create a duplicate telemetry record.

Therefore:

```text
DLQ Replay
     +
Same event_id
     +
Idempotency
     ↓
Safe Reprocessing
```

This is one of the reasons idempotency was implemented before the DLQ.

---

# DLQ Lifecycle

A DLQ event should have a clear lifecycle.

A simple lifecycle is:

```text
                    ┌──────────────┐
                    │  Dead Letter │
                    │    Event     │
                    └──────┬───────┘
                           │
                           ▼
                       pending
                           │
                           ▼
                     investigating
                           │
                           ▼
                        replayed
                           │
                    ┌──────┴──────┐
                    │             │
                  Success       Failure
                    │             │
                    ▼             ▼
                resolved       pending
```

### `pending`

The event has entered the DLQ and requires attention.

### `investigating`

An operator or automated process is investigating the failure.

### `replayed`

The event has been sent back to the processing pipeline.

### `resolved`

The event was successfully processed after replay.

If replay fails, the event should remain recoverable rather than being permanently discarded.

---

# Failure Scenarios

## Scenario 1 — Temporary Database Failure

```text
Telemetry
   ↓
Processing
   ↓
Database unavailable
   ↓
Retry
   ↓
Retry
   ↓
Retry
   ↓
DLQ
```

After the database problem is fixed:

```text
DLQ
 ↓
Replay
 ↓
Database available
 ↓
Success
```

---

## Scenario 2 — Invalid Telemetry

```text
Telemetry
   ↓
Validation
   ↓
Invalid device_id
   ↓
DLQ
```

The event does not go through unnecessary retries.

The operator can investigate the event and determine whether the data should be corrected or discarded.

---

## Scenario 3 — Worker Failure

```text
Queue
   ↓
Worker
   ↓
Processing
   ↓
Worker failure
```

The event may be redelivered.

If processing repeatedly fails:

```text
Retry
   ↓
Retry
   ↓
Retry
   ↓
DLQ
```

---

## Scenario 4 — Replay Succeeds

```text
DLQ
 ↓
Replay
 ↓
Processing
 ↓
Success
 ↓
Resolved
```

The DLQ status becomes:

```text
resolved
```

---

## Scenario 5 — Replay Fails

```text
DLQ
 ↓
Replay
 ↓
Processing
 ↓
Failure
```

The event remains available for further investigation or another controlled replay.

The system should not silently delete the event after a failed replay.

---

# Monitoring Considerations

The DLQ itself should be monitored.

Important metrics include:

### DLQ depth

```text
Number of events currently in DLQ
```

A sudden increase may indicate a system-wide problem.

### DLQ arrival rate

```text
DLQ events / minute
```

This helps identify abnormal failure patterns.

### Retry exhaustion rate

```text
Number of events reaching maximum retries
```

A high value can indicate infrastructure or processing problems.

### Non-retryable failure rate

Track events entering the DLQ because of invalid data.

This can reveal problems with telemetry producers.

### Replay success rate

```text
Successful replays
------------------
Total replay attempts
```

This helps measure the effectiveness of recovery.

### Age of oldest DLQ event

For example:

```text
Oldest DLQ event = 4 hours
```

This helps identify events that require operational attention.

---

# Operational Alerts

The system can eventually generate alerts when:

```text
DLQ depth > threshold
```

or:

```text
DLQ arrival rate increases significantly
```

or:

```text
Oldest DLQ event exceeds SLA
```

Example:

```text
DLQ depth
   ↓
Normal
   ↓
Sudden increase
   ↓
Monitoring Alert
   ↓
Engineering Investigation
```

This will become more important when observability is implemented later in the project.

---

# Trade-offs

## Advantages

### 1. Prevents Silent Data Loss

Failed telemetry is preserved rather than simply discarded.

### 2. Enables Recovery

Events can be replayed after the underlying problem is fixed.

### 3. Failure Isolation

Bad events are separated from the normal processing pipeline.

### 4. Easier Troubleshooting

The failure reason, payload and retry information are available for investigation.

### 5. Supports Operational Monitoring

DLQ metrics provide a useful indicator of system health.

### 6. Works With Idempotency

Events can be safely replayed without creating duplicate telemetry records.

---

## Disadvantages

### 1. Additional Infrastructure

The system needs additional storage or queue infrastructure for failed events.

### 2. Operational Complexity

Someone or something must monitor and manage DLQ events.

### 3. Storage Growth

If failed events are never resolved or cleaned up, the DLQ can grow continuously.

### 4. Replay Complexity

Replay must be carefully designed so that it does not create duplicates or bypass normal validation.

### 5. Failure Classification

Incorrectly classifying an error as retryable or non-retryable can result in unnecessary retries or premature DLQ placement.

### 6. Sensitive Data Considerations

The DLQ may contain the original telemetry payload.

Therefore, access controls, encryption and retention policies must be applied appropriately.

---

# Decision

The telemetry platform will use a Dead Letter Queue to isolate telemetry events that cannot be successfully processed.

An event enters the DLQ when:

```text
1. It encounters a non-retryable failure
OR
2. It exhausts the configured retry attempts
```

The DLQ will preserve:

```text
event_id
device_id
original_payload
error_type
failure_reason
attempt_count
failure timestamps
status
```

DLQ events will be recoverable through a replay mechanism.

Replay will send events through the normal telemetry processing pipeline and will continue to use the existing `event_id`-based idempotency mechanism.

The initial DLQ lifecycle is:

```text
pending
   ↓
investigating
   ↓
replayed
   ↓
resolved
```

Failed replays remain recoverable for further investigation.

---

# Result

The telemetry platform now has a complete failure-isolation path:

```text
                         Telemetry
                             │
                             ▼
                      Batch Ingestion
                             │
                             ▼
                        Processing
                             │
                       ┌─────┴─────┐
                       │           │
                    Success      Failure
                       │           │
                       ▼           ▼
                  Completed    Retryable?
                                   │
                             ┌─────┴─────┐
                             │           │
                            Yes          No
                             │           │
                             ▼           ▼
                       Retry/Backoff    DLQ
                             │
                             ▼
                       Max Attempts?
                             │
                        ┌────┴────┐
                        │         │
                       No        Yes
                        │         │
                        ▼         ▼
                      Retry      DLQ
                                  │
                          ┌───────┴───────┐
                          │               │
                     Investigation      Replay
                                          │
                                          ▼
                                   Normal Processing
                                          │
                                          ▼
                                     Idempotency
                                          │
                                          ▼
                                      Completed
```

The resulting reliability chain is:

```text
Idempotency
     +
Retry
     +
Backoff
     +
Dead Letter Queue
     +
Replay
     ↓
Reliable Telemetry Processing
```

This completes the **DLQ architectural design**. The next implementation stage is **61.12 — Async Processing**, where we move telemetry processing out of the synchronous ingestion path and introduce the queue/worker architecture.
