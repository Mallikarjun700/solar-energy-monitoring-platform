# ADR-00X: Telemetry Retry Handling and Failure Recovery

## Context

The telemetry platform processes events received from renewable-energy devices.

Telemetry processing may fail temporarily because of infrastructure or dependency failures such as database connectivity problems, message broker failures, network interruptions, Redis failures, or temporary service unavailability.

If the system immediately discards an event whenever processing fails, telemetry data can be lost.

At the same time, retrying every failure indefinitely can create additional problems:

* Repeated processing of permanently invalid events
* Increased infrastructure load
* Processing delays
* Queue congestion
* Repeated failures
* Resource consumption

Therefore, the telemetry processing pipeline requires a controlled retry mechanism that distinguishes between **temporary failures** and **permanent failures**.

---

# Why Retries Are Required

Retries allow the system to recover automatically from temporary failures without requiring manual intervention.

For example:

```text
Telemetry Event
      │
      ▼
Telemetry Processor
      │
      ▼
Database
      │
      X
Temporary connection failure
      │
      ▼
Retry
      │
      ▼
Database
      │
      ▼
Success
```

Without retries:

```text
Temporary failure
       ↓
Event rejected/lost
       ↓
Missing telemetry
       ↓
Incorrect analytics
```

With controlled retries:

```text
Temporary failure
       ↓
Retry
       ↓
Successful processing
```

Retries therefore improve the reliability and resilience of the telemetry pipeline.

---

# Retryable Failures

A failure should be retried when it is likely to be temporary.

Examples include:

### Database temporarily unavailable

```text
Database connection timeout
Database temporarily unavailable
Connection pool exhaustion
```

### Message broker failure

```text
Queue unavailable
Temporary broker connection failure
Message publishing timeout
```

### Redis failure

```text
Redis connection timeout
Temporary Redis unavailability
```

### Network failure

```text
Connection timeout
Temporary network interruption
Temporary DNS/service connectivity failure
```

### External service timeout

If telemetry processing depends on an external service and that service temporarily becomes unavailable:

```text
External service
      ↓
Timeout
      ↓
Retry
```

These failures may succeed when attempted again later.

---

# Non-Retryable Failures

Not every failure should be retried.

Permanent or invalid data should normally be rejected immediately.

Examples:

### Missing required fields

```text
device_id missing
event_id missing
timestamp missing
```

### Invalid data format

```text
Invalid JSON
Invalid timestamp
Invalid numeric value
Malformed payload
```

### Invalid device

```text
device_id does not exist
```

### Invalid telemetry value

For example:

```text
power_kw = invalid value
temperature = invalid value
```

Retrying the same invalid event will not fix the underlying problem.

Therefore:

```text
Temporary infrastructure failure
        ↓
Retry

Invalid business/data condition
        ↓
Do not retry
```

---

# Maximum Retry Count

The initial retry policy uses a maximum of **3 attempts**.

```text
Attempt 1
   ↓
Failure
   ↓
Attempt 2
   ↓
Failure
   ↓
Attempt 3
   ↓
Failure
   ↓
Dead Letter / Failed
```

The system must not retry an event indefinitely.

After the maximum retry count is reached, the event should be moved to the next failure-handling mechanism, which will be implemented as the **Dead Letter Queue** in the next stage of the architecture.

The maximum retry count is configurable so that it can be adjusted based on operational requirements.

---

# Retry Delay / Backoff

Retries should not happen immediately and continuously.

The initial retry schedule is:

```text
Attempt 1 → Initial processing
Attempt 2 → 5 seconds later
Attempt 3 → 30 seconds later
Attempt 4 → 2 minutes later
```

The delays reduce unnecessary pressure on dependencies that may already be experiencing problems.

Example:

```text
Database unavailable
       │
       ▼
Retry after 5 seconds
       │
       ▼
Still unavailable
       │
       ▼
Retry after 30 seconds
       │
       ▼
Still unavailable
       │
       ▼
Retry after 2 minutes
```

This approach is preferable to continuously retrying without a delay.

The retry delay should remain configurable so that it can be tuned as the system evolves.

---

# How Retry State Is Tracked

The processing system needs to know whether an event is being processed for the first time or is being retried.

The retry state can contain information such as:

```text
event_id
attempt_count
status
last_attempt_at
next_retry_at
```

Possible processing states include:

```text
pending
processing
completed
failed
retrying
dead_lettered
```

Example:

```text
event_id:        evt_123
attempt_count:   2
status:          retrying
last_attempt_at: 10:05:00
next_retry_at:   10:05:30
```

The retry metadata represents the **processing state**, while the telemetry record represents the actual telemetry data.

This separation allows the system to track processing failures without modifying the original telemetry measurement unnecessarily.

---

# Interaction With Idempotency

Retry handling and idempotency are closely related.

Retries can result in the same telemetry event being delivered multiple times.

For example:

```text
Attempt 1
   ↓
Process Event A
   ↓
Database write succeeds
   ↓
Worker fails before acknowledgement
```

The message is delivered again:

```text
Attempt 2
   ↓
Process Event A
```

The event retains the same:

```text
event_id = evt_123
```

The idempotency mechanism detects that the event has already been processed.

```text
Retry
  +
Same event_id
  ↓
Idempotency protection
  ↓
No duplicate telemetry record
```

Therefore:

> **Retries provide reliability, while idempotency provides duplicate protection.**

Both mechanisms are required for reliable distributed telemetry processing.

---

# Failure Scenarios

## Scenario 1 — Temporary Database Failure

```text
Telemetry Event
      ↓
Processor
      ↓
Database
      X
Connection timeout
```

The event is marked for retry.

```text
Retry #1
   ↓
Database available
   ↓
Success
```

The event becomes:

```text
completed
```

---

## Scenario 2 — Database Remains Unavailable

```text
Attempt 1 → Failure
Attempt 2 → Failure
Attempt 3 → Failure
```

After the configured maximum retry count:

```text
Event
  ↓
Dead Letter / Failed
```

The system stops retrying indefinitely.

---

## Scenario 3 — Invalid Telemetry

```text
Telemetry
   ↓
Validation
   ↓
Invalid device_id
```

This is a non-retryable error.

```text
No retry
   ↓
Rejected / Dead Letter
```

There is no benefit in repeatedly processing the same invalid event.

---

## Scenario 4 — Worker Failure After Successful Processing

```text
Queue
  ↓
Worker
  ↓
Store telemetry
  ↓
Worker crashes
```

The queue may redeliver the event.

```text
Redelivery
    ↓
Same event_id
    ↓
Idempotency check
    ↓
Duplicate
```

No second telemetry record is created.

---

## Scenario 5 — Dependency Recovery

```text
Attempt 1 → Database unavailable
Attempt 2 → Database unavailable
Attempt 3 → Database recovered
```

The third attempt succeeds:

```text
completed
```

This demonstrates why controlled retries are useful for transient infrastructure failures.

---

# Trade-offs

## Advantages

### 1. Improved Reliability

Temporary infrastructure failures do not immediately result in telemetry loss.

### 2. Automatic Recovery

The system can recover from temporary failures without manual intervention.

### 3. Controlled Resource Usage

Maximum retry limits prevent infinite retry loops.

### 4. Reduced Dependency Pressure

Backoff delays prevent the system from continuously hitting an unavailable dependency.

### 5. Better Distributed-System Resilience

The combination of retries and idempotency makes asynchronous processing safer.

---

## Disadvantages

### 1. Increased Processing Latency

An event that fails may take seconds or minutes before successful processing.

### 2. Additional Infrastructure Complexity

Retry scheduling and processing state introduce additional system components and logic.

### 3. Increased Load During Recovery

A large number of failed events can create a retry storm when a dependency recovers.

### 4. Additional Storage

Retry metadata requires additional storage or message metadata.

### 5. Incorrect Retry Classification

If a permanent failure is incorrectly classified as retryable, the system wastes resources repeatedly processing it.

If a temporary failure is incorrectly classified as non-retryable, valid telemetry may be lost or unnecessarily moved to failure handling.

---

# Decision

The telemetry platform will implement controlled retry handling using the following initial policy:

```text
Maximum attempts: 3

Retry delays:
5 seconds
30 seconds
2 minutes
```

The system will distinguish between:

```text
Retryable failures
        ↓
Retry with backoff

Non-retryable failures
        ↓
Do not retry
```

Retry state will track:

```text
event_id
attempt_count
status
last_attempt_at
next_retry_at
```

The retry mechanism will work together with the existing idempotency mechanism based on `event_id`.

After the maximum retry count is reached, failed events will be handled by the **Dead Letter Queue** mechanism.

---

# Result

The telemetry processing pipeline now has controlled failure recovery:

```text
                     Telemetry Event
                            │
                            ▼
                    Process Telemetry
                            │
                  ┌─────────┴─────────┐
                  │                   │
               Success              Failure
                  │                   │
                  ▼                   ▼
              Completed          Is Retryable?
                                      │
                             ┌────────┴────────┐
                             │                 │
                            Yes                No
                             │                 │
                             ▼                 ▼
                       Retry + Backoff    Failed/DLQ
                             │
                             ▼
                       Max Attempts?
                             │
                    ┌────────┴────────┐
                    │                 │
                   No                Yes
                    │                 │
                    ▼                 ▼
                 Retry             Failed/DLQ
```

Together with idempotency:

```text
                 Reliable Telemetry Processing
                            │
             ┌──────────────┴──────────────┐
             │                             │
          Retry                         Idempotency
             │                             │
     Recover temporary              Prevent duplicate
         failures                       records
             │                             │
             └──────────────┬──────────────┘
                            ▼
                   Resilient Pipeline
```

This establishes the retry foundation required before introducing the **Dead Letter Queue in 61.11**.
